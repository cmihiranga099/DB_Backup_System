<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backup_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('db_host')->default('127.0.0.1');
            $table->unsignedSmallInteger('db_port')->default(3306);
            $table->string('db_database');
            $table->string('db_username')->default('root');
            $table->text('db_password')->nullable(); // encrypted at rest
            $table->timestamps();
        });

        Schema::table('backup_schedules', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('name')
                ->constrained('backup_projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('backup_schedules', function (Blueprint $table) {
            $table->dropForeignIdFor(\BackupSuite\Models\BackupProject::class);
            $table->dropColumn('project_id');
        });
        Schema::dropIfExists('backup_projects');
    }
};
