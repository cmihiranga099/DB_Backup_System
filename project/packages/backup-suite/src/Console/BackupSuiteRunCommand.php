<?php

namespace BackupSuite\Console;

use BackupSuite\Jobs\RunBackupJob;
use BackupSuite\Models\BackupSchedule;
use Illuminate\Console\Command;

class BackupSuiteRunCommand extends Command
{
    protected $signature = 'backup-suite:run {schedule_id? : Optional schedule id to associate}';
    protected $description = 'Kick off a backup immediately.';

    public function handle(): int
    {
        $scheduleId = $this->argument('schedule_id');
        $schedule = $scheduleId ? BackupSchedule::find($scheduleId) : null;

        RunBackupJob::dispatch($schedule?->id, true);
        $this->info('Backup job dispatched' . ($schedule ? " for schedule {$schedule->name}" : ''));

        return self::SUCCESS;
    }
}
