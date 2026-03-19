<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    return redirect()->route('backup-suite.schedules.index');
});

Route::get('/health/backup-suite', function () {
    $status = [
        'db' => false,
        'tables' => [],
    ];

    try {
        DB::connection()->getPdo();
        $status['db'] = true;
        foreach (['backup_schedules', 'backup_runs', 'backup_settings'] as $table) {
            $status['tables'][$table] = Schema::hasTable($table);
        }
    } catch (\Throwable $e) {
        $status['error'] = $e->getMessage();
        return response()->json($status, 500);
    }

    if (in_array(false, $status['tables'], true)) {
        return response()->json($status, 500);
    }

    return response()->json($status);
});

use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingsController;


Route::middleware(config('backup-suite.middleware', ['web']))
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
