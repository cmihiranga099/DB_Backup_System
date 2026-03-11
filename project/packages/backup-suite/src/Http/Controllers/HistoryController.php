<?php

namespace BackupSuite\Http\Controllers;

use BackupSuite\Models\BackupRun;
use BackupSuite\Models\BackupSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(): View
    {
        $query = BackupRun::with('schedule')->orderByDesc('created_at');

        if ($scheduleId = request('schedule_id')) {
            $query->where('schedule_id', $scheduleId);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($from = request('from')) {
            $query->whereDate('started_at', '>=', $from);
        }

        if ($to = request('to')) {
            $query->whereDate('started_at', '<=', $to);
        }

        $runs      = $query->paginate(20);
        $schedules = BackupSchedule::orderBy('name')->get(['id', 'name']);

        return view('backup-suite::history.index', compact('runs', 'schedules'));
    }

    public function download(BackupRun $run): StreamedResponse|RedirectResponse
    {
        $disk = config('backup-suite.local_disk');
        if ($run->local_path && Storage::disk($disk)->exists($run->local_path)) {
            return Storage::disk($disk)->download($run->local_path, $run->filename);
        }

        $remote = config('backup-suite.remote_disk');
        if ($run->remote_path && $remote && Storage::disk($remote)->exists($run->remote_path)) {
            return Storage::disk($remote)->download($run->remote_path, $run->filename);
        }

        return redirect()->back()->with('error', 'Backup file not found on local or remote storage.');
    }
}
