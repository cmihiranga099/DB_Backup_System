<?php

namespace BackupSuite\Http\Controllers;

use BackupSuite\Models\BackupProject;
use BackupSuite\Models\BackupRun;
use BackupSuite\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = BackupProject::withCount('schedules')->orderByDesc('created_at')->get();
        return view('backup-suite::projects.index', compact('projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'db_host'     => ['required', 'string', 'max:255'],
            'db_port'     => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:100'],
            'db_username' => ['required', 'string', 'max:100'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        BackupProject::create($data);
        return redirect()->back()->with('status', 'Project "' . $data['name'] . '" added.');
    }

    public function update(Request $request, BackupProject $project): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'db_host'     => ['required', 'string', 'max:255'],
            'db_port'     => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:100'],
            'db_username' => ['required', 'string', 'max:100'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        // Keep existing password if blank
        if (empty($data['db_password'])) {
            unset($data['db_password']);
        }

        $project->update($data);
        return redirect()->back()->with('status', 'Project updated.');
    }

    public function destroy(BackupProject $project): RedirectResponse
    {
        $project->delete();
        return redirect()->back()->with('status', 'Project deleted.');
    }

    public function testConnection(BackupProject $project)
    {
        return response()->json($project->testConnection());
    }

    public function runBackup(BackupProject $project): RedirectResponse
    {
        $run = app(BackupService::class)->run(null, true, 'database', $project);
        $msg = $run->status === 'success'
            ? "Backup for \"{$project->name}\" completed successfully."
            : "Backup failed: {$run->message}";
        $key = $run->status === 'success' ? 'status' : 'error';
        return redirect()->back()->with($key, $msg);
    }
}
