# Backup Suite (Laravel + Blade + Tailwind)

## Install
1. `cd project`
2. `composer update backup-suite/backup-suite`
3. `php artisan backup-suite:install` (publishes config/views and runs migrations)
4. Add cron: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

## Env / config
- `BACKUP_SUITE_ENABLED=false` (default global toggle)
- `BACKUP_SUITE_LOCAL_DISK=local`
- `BACKUP_SUITE_LOCAL_FOLDER=backups`
- `BACKUP_SUITE_REMOTE_DISK=gdrive` (define disk in `config/filesystems.php`)
- `BACKUP_SUITE_REMOTE_FOLDER=backups`
- `BACKUP_SUITE_RETENTION=10`

Google Drive: use Flysystem v3 adapter with a service account JSON file and add a disk named `gdrive` in `filesystems.php` pointing to the credentials path.

## Usage (UI)
- Dashboard: `/backup-suite` for schedules and manual run button.
- History: `/backup-suite/history` for status, size, duration, download links.
- Settings: `/backup-suite/settings` to enable/disable automatic execution without removing cron.

## Usage (CLI)
- `php artisan backup-suite:run [schedule_id]` to dispatch a job immediately.
- `php artisan backup-suite:schedule --enable/--disable` to toggle automatic runs.
- `php artisan backup-suite:dispatch` is wired into the Laravel scheduler (runs every minute when cron calls `schedule:run`).

## Notes
- Queue-friendly: keep a worker running (`php artisan queue:work`).
- Supports MySQL via `mysqldump` on PATH; extend `BackupService` for other engines if needed.
- Retention enforces latest N backups per schedule; removes old local and remote copies.
