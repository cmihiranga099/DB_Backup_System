<?php

namespace App\Http\Controllers;

use App\Models\BackupSetting;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private GoogleDriveService $drive) {}

    public function index(): View
    {
        $enabled     = $this->currentState();
        $driveCreds  = $this->drive->credentials();
        $driveOk     = $this->drive->isConfigured();

        return view('settings.index', compact('enabled', 'driveCreds', 'driveOk'));
    }

    public function update(): RedirectResponse
    {
        $state = request()->boolean('enabled');
        BackupSetting::setValue('backup_suite_enabled', $state ? '1' : '0');

        return redirect()->back()->with('status', 'Settings saved.');
    }

    public function updateDrive(): RedirectResponse
    {
        $this->drive->saveCredentials([
            'client_id'     => request('client_id', ''),
            'client_secret' => request('client_secret', ''),
            'refresh_token' => request('refresh_token', ''),
            'folder_id'     => request('folder_id', ''),
        ]);

        return redirect()->back()->with('status', 'Google Drive credentials saved.');
    }

    public function testDrive(): JsonResponse
    {
        $result = $this->drive->testConnection();
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    protected function currentState(): bool
    {
        if (!Schema::hasTable('backup_settings')) {
            return (bool) config('backup-suite.global_enabled', false);
        }

        $dbFlag = BackupSetting::getValue('backup_suite_enabled');
        if (!is_null($dbFlag)) {
            return filter_var($dbFlag, FILTER_VALIDATE_BOOL);
        }

        return (bool) config('backup-suite.global_enabled', false);
    }
}
