<?php

namespace App\Services;

use App\Console\Commands\BackupSuiteDispatchCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;

class ScheduleRegistrar
{
    public function register(): void
    {
        $this->app()->afterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command(BackupSuiteDispatchCommand::class)
                ->name('backup-suite:dispatcher')
                ->everyMinute();
        });
    }

    protected function app(): \Illuminate\Foundation\Application
    {
        return app();
    }
}
