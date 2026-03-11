<?php

namespace BackupSuite\Http\Controllers;

use BackupSuite\Http\Requests\ScheduleRequest;
use BackupSuite\Jobs\RunBackupJob;
use BackupSuite\Models\BackupProject;
use BackupSuite\Models\BackupRun;
use BackupSuite\Models\BackupSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $schedules = BackupSchedule::with('project')->orderByDesc('created_at')->get();
        $recentRuns = BackupRun::with('schedule.project')->orderByDesc('created_at')->limit(10)->get();
        $projects = BackupProject::orderBy('name')->get();

        $totals = [
            'backups' => BackupRun::count(),
            'success' => BackupRun::where('status', 'success')->count(),
            'failed' => BackupRun::where('status', 'failed')->count(),
            'size_gb' => round((BackupRun::whereNotNull('size_bytes')->sum('size_bytes') / (1024 ** 3)), 2),
        ];

        return view('backup-suite::schedules.index', [
            'schedules' => $schedules,
            'recentRuns' => $recentRuns,
            'totals' => $totals,
            'projects' => $projects,
        ]);
    }

    public function store(ScheduleRequest $request): RedirectResponse
    {
        $data = $this->normalize($request->validated());
        $schedule = BackupSchedule::create($data);
        $schedule->next_run_at = $schedule->computeNextRun();
        $schedule->save();

        return redirect()->back()->with('status', 'Schedule created.');
    }

    public function update(ScheduleRequest $request, BackupSchedule $schedule): RedirectResponse
    {
        $data = $this->normalize($request->validated());
        $schedule->update($data);
        $schedule->next_run_at = $schedule->computeNextRun();
        $schedule->save();

        return redirect()->back()->with('status', 'Schedule updated.');
    }

    public function toggle(BackupSchedule $schedule): RedirectResponse
    {
        $schedule->update(['enabled' => !$schedule->enabled]);
        return redirect()->back()->with('status', 'Schedule ' . ($schedule->enabled ? 'enabled' : 'disabled') . '.');
    }

    public function destroy(BackupSchedule $schedule): RedirectResponse
    {
        $schedule->delete();
        return redirect()->back()->with('status', 'Schedule deleted.');
    }

    public function run(BackupSchedule $schedule): RedirectResponse
    {
        RunBackupJob::dispatch($schedule->id, true);
        return redirect()->back()->with('status', 'Backup dispatched.');
    }

    public function runManual(): RedirectResponse
    {
        $mode = request()->input('mode', 'database');
        RunBackupJob::dispatch(null, true, $mode);
        return redirect()->back()->with('status', 'Manual backup dispatched (' . $mode . ').');
    }

    protected function normalize(array $data): array
    {
        $defaults = [
            'timezone'   => $data['timezone'] ?: config('backup-suite.timezone'),
            'enabled'    => $data['enabled'] ?? false,
            'mode'       => $data['mode'] ?? 'database',
            'end_date'   => $data['end_date'] ?? null,
            'project_id' => $data['project_id'] ?? null,
        ];

        $paths = $data['file_paths'] ?? [];
        if (is_string($paths)) {
            $paths = preg_split('/\r\n|\r|\n/', $paths);
        } elseif (is_array($paths)) {
            $paths = collect($paths)
                ->flatMap(function ($item) {
                    return preg_split('/\r\n|\r|\n/', (string) $item);
                })
                ->all();
        }
        $defaults['file_paths'] = array_values(array_filter(array_map('strval', array_filter($paths, fn($p) => $p !== null))));

        if (($data['type'] ?? null) !== 'weekly') {
            $data['day_of_week'] = null;
        }
        if (($data['type'] ?? null) !== 'monthly' && ($data['type'] ?? null) !== 'yearly') {
            $data['day_of_month'] = null;
        }
        if (($data['type'] ?? null) !== 'yearly') {
            $data['month'] = null;
        }
        if (($data['type'] ?? null) !== 'custom') {
            $data['cron_expression'] = null;
        }

        return array_merge($data, $defaults);
    }
}
