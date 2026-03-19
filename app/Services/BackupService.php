<?php

namespace App\Services;

use App\Models\BackupProject;
use App\Models\BackupRun;
use App\Models\BackupSchedule;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use Symfony\Component\Process\Process;

class BackupService
{
    public function run(?BackupSchedule $schedule = null, bool $manual = false, string $manualMode = 'database', ?BackupProject $project = null): BackupRun
    {
        // Prefer the project linked to the schedule over an explicit project arg
        $resolvedProject = $schedule?->project ?? $project;
        $mode = $schedule ? $schedule->mode : $manualMode;
        $run = BackupRun::create([
            'schedule_id' => $schedule?->id,
            'status' => 'running',
            'manual' => $manual,
            'filename' => $this->buildFilename($schedule, $mode),
            'started_at' => now(),
        ]);

        try {
            [$localPath, $sizeBytes] = $this->generateBackup($schedule, $run->filename, $mode, $resolvedProject);
            $run->local_path = $localPath;
            $run->size_bytes = $sizeBytes;

            $remotePath = $this->uploadToRemote($localPath);
            $run->remote_path = $remotePath;

            $run->status = 'success';
            $run->message = 'Backup completed';
            $this->enforceRetention($schedule);
        } catch (Exception $e) {
            $run->status = 'failed';
            $run->message = $e->getMessage();
        } finally {
            $run->finished_at = now();
            $run->save();
        }

        return $run;
    }

    /**
     * Build backup according to mode.
     *
     * @return array [path, sizeBytes]
     */
    protected function generateBackup(?BackupSchedule $schedule, string $filename, string $mode, ?BackupProject $project = null): array
    {
        if ($mode === 'database') {
            $path = $this->dumpDatabase($filename . '.sql', $project);
            $size = Storage::disk($this->localDisk())->size($path);
            return [$path, $size];
        }

        $zipName = $filename . '.zip';
        $zipPath = $this->zipPayload($zipName, function (ZipArchive $zip) use ($mode, $schedule, $filename, $project) {
            if ($mode === 'files' || $mode === 'both') {
                $paths = $schedule?->file_paths ?? config('backup-suite.default_file_paths', []);
                $this->addPathsToZip($zip, $paths);
            }

            if ($mode === 'both') {
                $sqlName = $filename . '.sql';
                $sqlPath = $this->dumpDatabase($sqlName, $project);
                $disk = Storage::disk($this->localDisk());
                if ($disk->exists($sqlPath)) {
                    $zip->addFromString($sqlName, $disk->get($sqlPath));
                }
            }
        });

        $size = Storage::disk($this->localDisk())->size($zipPath);
        return [$zipPath, $size];
    }

    protected function dumpDatabase(string $filename, ?BackupProject $project = null): string
    {
        if ($project) {
            $config = $project->dbConfig();
        } else {
            $connection = Config::get('database.default');
            $config = Config::get("database.connections.$connection");
        }

        if (($config['driver'] ?? 'mysql') !== 'mysql') {
            throw new Exception('Only MySQL backups are supported in this build.');
        }

        $disk = Storage::disk($this->localDisk());
        $folder = trim(config('backup-suite.local_folder', 'backups'), '/');
        $disk->makeDirectory($folder);

        $path = $folder . '/' . $filename;
        $fullPath = $disk->path($path);

        $cmd = [
            config('backup-suite.mysqldump_path', 'mysqldump'),
            '--host=' . $config['host'],
            '--port=' . ($config['port'] ?? 3306),
            '--user=' . $config['username'],
            '--password=' . ($config['password'] ?? ''),
            '--result-file=' . $fullPath,
            $config['database'],
        ];

        $process = new Process($cmd);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception('mysqldump failed: ' . $process->getErrorOutput());
        }

        return $path;
    }

    protected function zipPayload(string $filename, callable $callback): string
    {
        $disk = Storage::disk($this->localDisk());
        $folder = trim(config('backup-suite.local_folder', 'backups'), '/');
        $disk->makeDirectory($folder);

        $path = $folder . '/' . $filename;
        $fullPath = $disk->path($path);

        $zip = new ZipArchive();
        if (true !== $zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new Exception('Could not create archive.');
        }

        $callback($zip);
        $zip->close();

        return $path;
    }

    protected function addPathsToZip(ZipArchive $zip, array $paths): void
    {
        if (empty($paths)) {
            throw new Exception('No file paths configured for file backup.');
        }

        foreach ($paths as $path) {
            $absolute = base_path($path);
            if (!file_exists($absolute)) {
                continue;
            }

            if (is_dir($absolute)) {
                $this->addDirectoryToZip($zip, $absolute, trim($path, '/'));
            } else {
                $zip->addFile($absolute, basename($absolute));
            }
        }
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $dir, string $prefix = ''): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $localName = $prefix . '/' . ltrim(str_replace($dir, '', $file->getPathname()), '/\\');
            if ($file->isDir()) {
                $zip->addEmptyDir($localName);
            } else {
                $zip->addFile($file->getPathname(), $localName);
            }
        }
    }

    protected function uploadToRemote(string $path): ?string
    {
        $remoteDisk = $this->remoteDisk();
        if (!$remoteDisk || !Config::has("filesystems.disks.$remoteDisk")) {
            return null;
        }

        $remote = trim(config('backup-suite.remote_folder', 'backups'), '/');
        $remotePath = $remote . '/' . basename($path);

        $stream = Storage::disk($this->localDisk())->readStream($path);
        Storage::disk($remoteDisk)->put($remotePath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $remotePath;
    }

    protected function enforceRetention(?BackupSchedule $schedule): void
    {
        if (!$schedule) {
            return;
        }

        $keep = (int) config('backup-suite.retention.per_schedule', 10);
        $runs = $schedule->runs()->orderByDesc('created_at')->get();

        if ($runs->count() <= $keep) {
            return;
        }

        $runs->slice($keep)->each(function (BackupRun $run) {
            if ($run->local_path) {
                Storage::disk($this->localDisk())->delete($run->local_path);
            }
            if ($run->remote_path && $this->remoteDisk()) {
                Storage::disk($this->remoteDisk())->delete($run->remote_path);
            }
            $run->delete();
        });
    }

    protected function buildFilename(?BackupSchedule $schedule, string $mode): string
    {
        $prefix = $schedule ? Str::slug($schedule->name) : 'manual';
        $suffix = $mode === 'database' ? 'db' : ($mode === 'files' ? 'files' : 'full');
        return $prefix . '_' . $suffix . '_' . now()->format('Ymd_His');
    }

    protected function localDisk(): string
    {
        return config('backup-suite.local_disk', 'local');
    }

    protected function remoteDisk(): ?string
    {
        return config('backup-suite.remote_disk');
    }
}
