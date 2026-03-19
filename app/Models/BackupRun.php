<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'status',
        'manual',
        'filename',
        'local_path',
        'remote_path',
        'size_bytes',
        'started_at',
        'finished_at',
        'message',
    ];

    protected $casts = [
        'manual' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(BackupSchedule::class, 'schedule_id');
    }
}
