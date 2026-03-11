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
