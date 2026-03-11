<?php

use BackupSuite\Http\Controllers\HistoryController;
use BackupSuite\Http\Controllers\ProjectController;
use BackupSuite\Http\Controllers\ScheduleController;
use BackupSuite\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('backup-suite.middleware', ['web', 'auth']))
    ->prefix(config('backup-suite.route_prefix', 'backup-suite'))
    ->name('backup-suite.')
    ->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
        Route::post('/schedules/{schedule}/run', [ScheduleController::class, 'run'])->name('schedules.run');
        Route::post('/schedules/{schedule}/toggle', [ScheduleController::class, 'toggle'])->name('schedules.toggle');
        Route::post('/run-manual', [ScheduleController::class, 'runManual'])->name('run.manual');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('/projects/{project}/test', [ProjectController::class, 'testConnection'])->name('projects.test');
        Route::post('/projects/{project}/backup', [ProjectController::class, 'runBackup'])->name('projects.backup');

        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
        Route::get('/history/{run}/download', [HistoryController::class, 'download'])->name('history.download');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/drive', [SettingsController::class, 'updateDrive'])->name('settings.drive.update');
        Route::post('/settings/drive/test', [SettingsController::class, 'testDrive'])->name('settings.drive.test');
    });
