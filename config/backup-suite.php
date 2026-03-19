<?php

return [
    'middleware' => ['web'],
    'route_prefix' => 'backup-suite',
    'timezone' => env('APP_TIMEZONE', 'Asia/Colombo'),
    'local_folder' => env('BACKUP_SUITE_LOCAL_FOLDER', 'backups'),
    'remote_disk' => env('BACKUP_SUITE_REMOTE_DISK', 'gdrive'),
    'remote_folder' => env('BACKUP_SUITE_REMOTE_FOLDER', 'backups'),
    'default_file_paths' => [
        'storage/app',
        'public',
    ],
    'retention' => [
        'per_schedule' => env('BACKUP_SUITE_RETENTION', 10),
    ],
    'global_enabled' => env('BACKUP_SUITE_ENABLED', false),

    // Full path to mysqldump binary. On Windows/XAMPP set BACKUP_SUITE_MYSQLDUMP_PATH in .env
    'mysqldump_path' => env('BACKUP_SUITE_MYSQLDUMP_PATH', 'mysqldump'),
    'pgdump_path' => env('BACKUP_SUITE_PGDUMP_PATH', 'pg_dump'),
    'mongodump_path' => env('BACKUP_SUITE_MONGODUMP_PATH', 'mongodump'),
];
