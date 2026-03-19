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
            $table->string('ssh_host')->nullable()->after('db_password');
            $table->unsignedSmallInteger('ssh_port')->default(22)->after('ssh_host');
            $table->string('ssh_username')->nullable()->after('ssh_port');
            $table->text('ssh_password')->nullable()->after('ssh_username');
            $table->text('ssh_private_key')->nullable()->after('ssh_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_projects', function (Blueprint $table) {
            $table->dropColumn(['ssh_host', 'ssh_port', 'ssh_username', 'ssh_password', 'ssh_private_key']);
        });
    }
};
