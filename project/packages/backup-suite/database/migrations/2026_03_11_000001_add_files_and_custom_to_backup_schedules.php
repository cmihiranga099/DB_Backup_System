<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('backup_schedules', function (Blueprint $table) {
            $table->enum('mode', ['database', 'files', 'both'])->default('database')->after('type');
            $table->json('file_paths')->nullable()->after('mode');
            $table->string('cron_expression')->nullable()->after('time');
        });
    }

    public function down(): void
    {
        Schema::table('backup_schedules', function (Blueprint $table) {
            $table->dropColumn(['mode', 'file_paths', 'cron_expression']);
        });
    }
};
