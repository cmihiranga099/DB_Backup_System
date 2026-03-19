<?php

namespace BackupSuite;

use BackupSuite\Console\BackupSuiteDispatchCommand;
use BackupSuite\Console\BackupSuiteInstallCommand;
use BackupSuite\Console\BackupSuiteRunCommand;
use BackupSuite\Console\BackupSuiteToggleCommand;
use BackupSuite\Services\GoogleDriveService;
use BackupSuite\Services\ScheduleRegistrar;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class BackupSuiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/backup-suite.php', 'backup-suite');
        $this->app->singleton(ScheduleRegistrar::class);
        $this->app->singleton(GoogleDriveService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'backup-suite');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/backup-suite.php' => config_path('backup-suite.php'),
        ], 'backup-suite-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/backup-suite'),
        ], 'backup-suite-views');

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackupSuiteInstallCommand::class,
                BackupSuiteRunCommand::class,
                BackupSuiteToggleCommand::class,
                BackupSuiteDispatchCommand::class,
            ]);
        }

        $this->registerGoogleDriver();
        $this->bootRoutesMacro();
        $this->app->make(ScheduleRegistrar::class)->register();
        $this->app->make(GoogleDriveService::class)->registerDisk();
    }

    protected function registerGoogleDriver(): void
    {
        Storage::extend('google', function ($app, $config) {
            $client = new \Google\Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->setAccessType('offline');
            $client->fetchAccessTokenWithRefreshToken($config['refreshToken']);

            $service  = new \Google\Service\Drive($client);
            $adapter  = new \Masbug\Flysystem\GoogleDriveAdapter(
                $service,
                $config['folderId'] ?? '/'
            );
            $filesystem = new \League\Flysystem\Filesystem($adapter);

            return new \Illuminate\Filesystem\FilesystemAdapter($filesystem, $adapter);
        });
    }

    protected function bootRoutesMacro(): void
    {
        Route::macro('backupSuite', function () {
            Route::middleware(config('backup-suite.middleware', ['web', 'auth']))
                ->prefix(config('backup-suite.route_prefix', 'backup-suite'))
                ->name('backup-suite.')
                ->group(__DIR__ . '/../routes/web.php');
        });
    }
}
