<?php

namespace BackupSuite\Console;

use BackupSuite\Models\BackupSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackupSuiteToggleCommand extends Command
{
    protected $signature = 'backup-suite:schedule {--enable} {--disable}';
    protected $description = 'Enable or disable automatic scheduled backups.';

    public function handle(): int
    {
        $enable = $this->option('enable');
        $disable = $this->option('disable');

        if ($enable && $disable) {
            $this->error('Choose either --enable or --disable, not both.');
            return self::INVALID;
        }

        if (!$enable && !$disable) {
            $current = $this->current();
            $this->info('Current state: ' . ($current ? 'enabled' : 'disabled'));
            return self::SUCCESS;
        }

        $state = $enable;
        BackupSetting::setValue('backup_suite_enabled', $state ? '1' : '0');
        $this->info('Scheduled backups ' . ($state ? 'enabled' : 'disabled') . '.');

        return self::SUCCESS;
    }

    protected function current(): bool
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
