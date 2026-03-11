<?php

namespace BackupSuite\Jobs;

use BackupSuite\Models\BackupSchedule;
use BackupSuite\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $scheduleId = null,
        public bool $manual = false,
        public string $manualMode = 'database',
    ) {}

    public function handle(BackupService $service): void
    {
        $schedule = $this->scheduleId ? BackupSchedule::find($this->scheduleId) : null;
        $service->run($schedule, $this->manual, $this->manualMode);

        if ($schedule) {
            $schedule->next_run_at = $schedule->computeNextRun();
            $schedule->save();
        }
    }
}
