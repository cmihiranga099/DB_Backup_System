<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'project_id',
        'type',
        'mode',
        'day_of_week',
        'day_of_month',
        'month',
        'time',
        'cron_expression',
        'timezone',
        'start_date',
        'end_date',
        'enabled',
        'next_run_at',
        'file_paths',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_at' => 'datetime',
        'file_paths' => 'array',
    ];

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->endOfDay()->isPast();
    }

    public function project()
    {
        return $this->belongsTo(BackupProject::class, 'project_id');
    }

    public function runs()
    {
        return $this->hasMany(BackupRun::class, 'schedule_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function timezoneOrDefault(): string
    {
        $tz = $this->timezone ?: config('backup-suite.timezone', 'Asia/Colombo');
        try {
            new \DateTimeZone($tz);
            return $tz;
        } catch (\Exception $e) {
            return config('backup-suite.timezone', 'Asia/Colombo');
        }
    }

    public function computeNextRun(?Carbon $from = null): Carbon
    {
        $tz = $this->timezoneOrDefault();
        $reference = $from ? $from->copy()->setTimezone($tz) : Carbon::now($tz);

        if ($this->start_date) {
            $startOfDay = Carbon::parse($this->start_date->format('Y-m-d') . ' 00:00:00', $tz);
            if ($startOfDay->greaterThan($reference)) {
                $reference = $startOfDay;
            }
        }

        return match ($this->type) {
            'weekly' => $this->nextWeekly($reference),
            'monthly' => $this->nextMonthly($reference),
            'yearly' => $this->nextYearly($reference),
            'custom' => $this->nextCustom($reference),
            default => $this->nextDaily($reference),
        };
    }

    protected function nextDaily(Carbon $reference): Carbon
    {
        $candidate = Carbon::parse($reference->toDateString() . ' ' . $this->time, $reference->timezone);
        if ($candidate->lessThanOrEqualTo($reference)) {
            $candidate->addDay();
        }
        return $candidate;
    }

    protected function nextWeekly(Carbon $reference): Carbon
    {
        $dow = $this->day_of_week ?? 1; // default Monday
        $candidate = Carbon::parse($reference->toDateString() . ' ' . $this->time, $reference->timezone)
            ->nextOrSame($dow);

        if ($candidate->lessThanOrEqualTo($reference)) {
            $candidate->addWeek();
        }

        return $candidate;
    }

    protected function nextMonthly(Carbon $reference): Carbon
    {
        $day = $this->day_of_month ?? 1;
        $candidate = Carbon::create(
            $reference->year,
            $reference->month,
            min($day, 28),
            (int) substr($this->time, 0, 2),
            (int) substr($this->time, 3, 2),
            0,
            $reference->timezone
        )->day($day);

        if ($candidate->lessThanOrEqualTo($reference)) {
            $candidate->addMonthNoOverflow();
        }

        return $candidate;
    }

    protected function nextYearly(Carbon $reference): Carbon
    {
        $month = $this->month ?? 1;
        $day = $this->day_of_month ?? 1;

        $candidate = Carbon::create(
            $reference->year,
            $month,
            min($day, 28),
            (int) substr($this->time, 0, 2),
            (int) substr($this->time, 3, 2),
            0,
            $reference->timezone
        )->day($day);

        if ($candidate->lessThanOrEqualTo($reference)) {
            $candidate->addYear();
        }

        return $candidate;
    }

    protected function nextCustom(Carbon $reference): Carbon
    {
        if (!$this->cron_expression) {
            return $this->nextDaily($reference);
        }

        $cron = new \Cron\CronExpression($this->cron_expression);
        $next = $cron->getNextRunDate($reference);

        return Carbon::instance($next)->setTimezone($reference->timezone);
    }
}
