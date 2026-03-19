#!/bin/bash
# Laravel Production Deployment Script

echo "🚀 Starting Deployment..."

# 1. Enter Maintenance Mode
php artisan down || true

# 2. Update Code
git pull origin main

# 3. Install/Update Dependencies
composer install --no-dev --optimize-autoloader

# 4. Migrate Database
php artisan migrate --force

# 5. Optimize Laravel
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache

# 6. Restart Queue Workers
sudo supervisorctl restart laravel-worker:*

# 7. Exit Maintenance Mode
php artisan up

echo "✅ Backup System is now Live and 24/7!"
