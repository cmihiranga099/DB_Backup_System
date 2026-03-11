<?php

namespace BackupSuite\Console;

use Illuminate\Console\Command;

class BackupSuiteInstallCommand extends Command
{
    protected $signature = 'backup-suite:install {--force : Overwrite existing files}';
    protected $description = 'Publish config and run migrations for Backup Suite.';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'backup-suite-config',
            '--force' => $this->option('force'),
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'backup-suite-views',
            '--force' => $this->option('force'),
        ]);

        $this->call('migrate');

        $this->info('Backup Suite installed.');
        return self::SUCCESS;
    }
}
