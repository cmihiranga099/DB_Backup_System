<?php

namespace App\Console\Commands;

use App\Jobs\RunBackupJob;
use App\Models\BackupSchedule;
use App\Models\BackupSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackupSuiteDispatchCommand extends Command
{
    protected $signature = 'backup-suite:dispatch';
    protected $description = 'Dispatch due backup jobs based on saved schedules.';

    // Symfony expects this to be public; use it to gate command availability.
    public function isEnabled(): bool
    {
        return $this->shouldRun();
    }

    public function handle(): int
    {
        if (!$this->shouldRun()) {
            return self::SUCCESS;
        }

        $now = now();
        $due = BackupSchedule::enabled()->get()->filter(function (BackupSchedule $schedule) use ($now) {
            if ($schedule->isExpired()) {
                $schedule->enabled = false;
                $schedule->save();
                return false;
            }
            if (!$schedule->next_run_at) {
                $schedule->next_run_at = $schedule->computeNextRun($now);
                $schedule->save();
            }
            return $schedule->next_run_at && $schedule->next_run_at->lessThanOrEqualTo($now);
        });

        foreach ($due as $schedule) {
            RunBackupJob::dispatch($schedule->id, false);
            $schedule->next_run_at = $schedule->computeNextRun($now);
            $schedule->save();
        }

        return self::SUCCESS;
    }

    protected function shouldRun(): bool
    {
        if (!Schema::hasTable('backup_settings')) {
            return (bool) config('backup-suite.global_enabled', false);
        }

        $dbFlag = BackupSetting::getValue('backup_suite_enabled');
        if (!is_null($dbFlag)) {
            return filter_var($dbFlag, FILTER_VALIDATE_BOOL);
        }

        return (bool) config('backup-suite.global_enabled', false);
    }
}
