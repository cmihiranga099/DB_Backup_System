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
            $path = $this->dumpDatabase($filename, $project);
            $size = Storage::disk($this->localDisk())->size($path);
            return [$path, $size];
        }

        $zipName = $filename . '.zip';
        $zipPath = $this->zipPayload($zipName, function (ZipArchive $zip) use ($mode, $schedule, $filename, $project) {
            if ($mode === 'files' || $mode === 'both') {
                $paths = $schedule?->file_paths ?? config('backup-suite.default_file_paths', []);
                $this->addPathsToZip($zip, $paths, $project);
            }

            if ($mode === 'both') {
                $dumpPath = $this->dumpDatabase($filename, $project);
                $disk = Storage::disk($this->localDisk());
                if ($disk->exists($dumpPath)) {
                    $zip->addFromString(basename($dumpPath), $disk->get($dumpPath));
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

        $driver = $config['driver'] ?? 'mysql';
        $ext = $driver === 'mongodb' ? '.archive' : '.sql';
        $filename = $filename . $ext;

        $disk = Storage::disk($this->localDisk());
        $folder = trim(config('backup-suite.local_folder', 'backups'), '/');
        $disk->makeDirectory($folder);

        $path = $folder . '/' . $filename;
        $fullPath = $disk->path($path);

        $cmd = [];
        if ($driver === 'mysql') {
            $cmd = [
                config('backup-suite.mysqldump_path', 'mysqldump'),
                '--host=' . $config['host'],
                '--port=' . ($config['port'] ?? 3306),
                '--user=' . $config['username'],
                '--password=' . ($config['password'] ?? ''),
                '--result-file=' . $fullPath,
                $config['database'],
            ];
        } elseif ($driver === 'pgsql') {
            // Need to set PGPASSWORD env variable since pg_dump doesn't accept password flag securely
            $cmd = [
                config('backup-suite.pgdump_path', 'pg_dump'),
                '-h', $config['host'],
                '-p', (string) ($config['port'] ?? 5432),
                '-U', $config['username'],
                '-F', 'p', // plain text sql
                '-f', $fullPath,
                $config['database'],
            ];
        } elseif ($driver === 'mongodb') {
            $cmd = [
                config('backup-suite.mongodump_path', 'mongodump'),
                '--host=' . $config['host'],
                '--port=' . ($config['port'] ?? 27017),
                '--db=' . $config['database'],
                '--archive=' . $fullPath,
            ];
            if (!empty($config['username'])) {
                $cmd[] = '--username=' . $config['username'];
                $cmd[] = '--password=' . ($config['password'] ?? '');
            }
        } else {
            throw new Exception("Driver {$driver} is not fully supported for dumping yet.");
        }

        $process = new Process($cmd);
        if ($driver === 'pgsql' && !empty($config['password'])) {
            $process->setEnv(['PGPASSWORD' => $config['password']]);
        }
        
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception("Database dump failed ({$driver}): " . escapeshellarg($process->getErrorOutput()));
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

    protected function addPathsToZip(ZipArchive $zip, array $paths, ?BackupProject $project = null): void
    {
        if (empty($paths)) {
            throw new Exception('No file paths configured for file backup.');
        }

        // If project has SSH, we use an on-the-fly SFTP disk
        if ($project && $project->ssh_host) {
            $sftpDisk = Storage::build($project->sftpConfig());
            $this->addRemotePathsToZip($zip, $sftpDisk, $paths);
            return;
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

    protected function addRemotePathsToZip(ZipArchive $zip, $sftpDisk, array $paths): void
    {
        foreach ($paths as $path) {
            if (!$sftpDisk->exists($path)) {
                continue;
            }

            if ($this->isRemoteDir($sftpDisk, $path)) {
                $this->addRemoteDirectoryToZip($zip, $sftpDisk, $path);
            } else {
                $stream = $sftpDisk->readStream($path);
                if ($stream) {
                    $zip->addFromString(basename($path), stream_get_contents($stream));
                    fclose($stream);
                }
            }
        }
    }

    protected function isRemoteDir($disk, string $path): bool
    {
        // Simple heuristic for Flysystem
        return $disk->directoryExists($path);
    }

    protected function addRemoteDirectoryToZip(ZipArchive $zip, $disk, string $dir): void
    {
        $files = $disk->allFiles($dir);
        foreach ($files as $file) {
            $stream = $disk->readStream($file);
            if ($stream) {
                $zip->addFromString($file, stream_get_contents($stream));
                fclose($stream);
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
