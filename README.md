# DB Backup System

A Laravel 12 database backup management system built as a self-contained local package. Schedule, run, and store database backups with automatic Google Drive upload and a clean admin UI.

## Features

- **Multi-project support** — register multiple Laravel applications and back up each project's database independently
- **Flexible scheduling** — daily, weekly, monthly, yearly, and custom cron expressions
- **Google Drive upload** — automatic off-site backup after every run; credentials configured through the UI (no `.env` editing)
- **Backup history** — filterable log of all runs with status, file size, duration, and direct download
- **Manual backups** — trigger an immediate backup from the dashboard at any time
- **Per-schedule toggle** — enable or disable individual schedules without deleting them
- **Auto-expiry** — schedules with an end date disable themselves automatically
- **Sidebar UI** — fixed sidebar navigation with right-side slide-in drawer for create/edit

## Tech Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12 |
| Frontend CSS | Tailwind CSS v4 (`@tailwindcss/vite`) |
| Asset build | Vite 7 |
| Google Drive | `masbug/flysystem-google-drive-ext` |
| DB dump | `mysqldump` (path configurable) |
| Queue | Sync (immediate execution) |

## Package Structure

```
packages/backup-suite/
├── config/backup-suite.php          # Package configuration
├── database/migrations/             # All DB schema
├── resources/views/                 # Blade views (layouts, schedules, history, settings, projects)
├── routes/web.php                   # All package routes
└── src/
    ├── BackupSuiteServiceProvider.php
    ├── Console/                     # Artisan commands (dispatch, install, run, toggle)
    ├── Http/
    │   ├── Controllers/             # Schedule, History, Settings, Project controllers
    │   └── Requests/ScheduleRequest.php
    ├── Jobs/RunBackupJob.php
    ├── Models/                      # BackupSchedule, BackupRun, BackupSetting, BackupProject
    └── Services/
        ├── BackupService.php        # Core backup logic (dump + zip + upload)
        ├── GoogleDriveService.php   # Drive credential management and disk registration
        └── ScheduleRegistrar.php    # Next-run calculation and scheduler integration
```

## Installation

```bash
# 1. Clone and install dependencies
git clone https://github.com/cmihiranga099/DB_Backup_System.git
cd DB_Backup_System/project
composer install
npm install && npm run build

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env — set DB_DATABASE, DB_USERNAME, DB_PASSWORD
# For Windows/XAMPP, also set:
# BACKUP_SUITE_MYSQLDUMP_PATH=C:/xampp/mysql/bin/mysqldump.exe

# 3. Run migrations
php artisan migrate

# 4. Start dev server
php artisan serve
```

## Accessing the UI

Navigate to `http://localhost:8000/backup-suite`

| Route | Description |
|---|---|
| `/backup-suite/projects` | Manage Laravel projects |
| `/backup-suite` | Schedule management dashboard |
| `/backup-suite/history` | Backup history and downloads |
| `/backup-suite/settings` | Google Drive OAuth2 credentials |

## Google Drive Setup

1. Go to **Settings** in the sidebar
2. Follow the on-screen guide to create a Google Cloud OAuth2 client
3. Enter Client ID, Client Secret, Refresh Token, and Folder ID
4. Click **Test Connection** to verify

## Automating Schedules (Production)

Add to crontab:
```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

The package registers `php artisan backup-suite:dispatch` to run every minute and fires due schedules.

## Environment Variables

| Variable | Default | Description |
|---|---|---|
| `BACKUP_SUITE_MYSQLDUMP_PATH` | `mysqldump` | Full path to mysqldump binary |
| `BACKUP_SUITE_LOCAL_DISK` | `local` | Laravel filesystem disk for local storage |
| `BACKUP_SUITE_LOCAL_FOLDER` | `backups` | Subfolder within the local disk |
| `BACKUP_SUITE_REMOTE_DISK` | `gdrive` | Filesystem disk for remote upload |
| `BACKUP_SUITE_RETENTION` | `10` | Max backups to keep per schedule |

## License

MIT
