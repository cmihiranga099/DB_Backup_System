<?php

namespace BackupSuite\Services;

use BackupSuite\Models\BackupSetting;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private const KEYS = [
        'client_id'     => 'gdrive_client_id',
        'client_secret' => 'gdrive_client_secret',
        'refresh_token' => 'gdrive_refresh_token',
        'folder_id'     => 'gdrive_folder_id',
    ];

    /** Load all saved credentials from DB. */
    public function credentials(): array
    {
        $out = [];
        foreach (self::KEYS as $field => $key) {
            $out[$field] = BackupSetting::getValue($key, '');
        }
        return $out;
    }

    /** Return true if all required credentials are saved. */
    public function isConfigured(): bool
    {
        $creds = $this->credentials();
        return !empty($creds['client_id'])
            && !empty($creds['client_secret'])
            && !empty($creds['refresh_token']);
    }

    /** Save credentials to DB and reconfigure the disk for this request. */
    public function saveCredentials(array $data): void
    {
        foreach (self::KEYS as $field => $key) {
            $value = $data[$field] ?? '';
            // Keep existing value if blank (allows partial update)
            if ($value !== '') {
                BackupSetting::setValue($key, $value);
            }
        }
        $this->registerDisk();
    }

    /** Register the Flysystem Google disk at runtime from DB values. */
    public function registerDisk(): bool
    {
        if (!Schema::hasTable('backup_settings')) {
            return false;
        }

        $creds = $this->credentials();
        if (!$creds['client_id'] || !$creds['client_secret'] || !$creds['refresh_token']) {
            return false;
        }

        $diskName = config('backup-suite.remote_disk', 'gdrive');

        Config::set("filesystems.disks.$diskName", [
            'driver'        => 'google',
            'clientId'      => $creds['client_id'],
            'clientSecret'  => $creds['client_secret'],
            'refreshToken'  => $creds['refresh_token'],
            'folderId'      => $creds['folder_id'] ?: null,
        ]);

        return true;
    }

    /** Test the connection by listing the root of Drive. */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'Credentials not saved yet.'];
        }

        $this->registerDisk();
        $diskName = config('backup-suite.remote_disk', 'gdrive');

        try {
            Storage::disk($diskName)->files('/');
            return ['ok' => true, 'message' => 'Connected to Google Drive successfully.'];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }
}
