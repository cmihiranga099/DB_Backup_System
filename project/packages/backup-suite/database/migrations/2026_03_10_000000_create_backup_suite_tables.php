<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0=Sun
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1-31
            $table->unsignedTinyInteger('month')->nullable(); // 1-12 for yearly
            $table->time('time');
            $table->string('timezone')->nullable();
            $table->date('start_date')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->nullable()->constrained('backup_schedules')->nullOnDelete();
            $table->enum('status', ['pending', 'running', 'success', 'failed'])->default('pending');
            $table->boolean('manual')->default(false);
            $table->string('filename');
            $table->string('local_path')->nullable();
            $table->string('remote_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
        Schema::dropIfExists('backup_runs');
        Schema::dropIfExists('backup_schedules');
    }
};
