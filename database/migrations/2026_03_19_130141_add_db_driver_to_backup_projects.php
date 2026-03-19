<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('backup_projects', function (Blueprint $table) {
            $table->string('db_driver', 50)->default('mysql')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_projects', function (Blueprint $table) {
            $table->dropColumn('db_driver');
        });
    }
};
