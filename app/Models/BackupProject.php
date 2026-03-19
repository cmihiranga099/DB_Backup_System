<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BackupProject extends Model
{
    protected $fillable = [
        'name',
        'description',
        'db_host',
        'db_port',
        'db_database',
        'db_username',
        'db_password',
    ];

    protected function dbPassword(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value !== null ? encrypt($value) : null,
        );
    }

    public function schedules()
    {
        return $this->hasMany(BackupSchedule::class, 'project_id');
    }

    /** Return an array suitable for passing to BackupService as DB config. */
    public function dbConfig(): array
    {
        return [
            'driver'   => 'mysql',
            'host'     => $this->db_host,
            'port'     => $this->db_port,
            'database' => $this->db_database,
            'username' => $this->db_username,
            'password' => $this->db_password ?? '',
        ];
    }

    /** Test the DB connection without persisting. Returns ['ok'=>bool, 'message'=>string]. */
    public function testConnection(): array
    {
        try {
            $pdo = new \PDO(
                "mysql:host={$this->db_host};port={$this->db_port};dbname={$this->db_database}",
                $this->db_username,
                $this->db_password ?? '',
                [\PDO::ATTR_TIMEOUT => 5, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            return ['ok' => true, 'message' => "Connected — MySQL {$version}"];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
