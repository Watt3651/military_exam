#!/usr/bin/env bash

set -euo pipefail

APP_DIR="/var/www/millitary_exam"

echo "==> Deploy started"
cd "$APP_DIR"

echo "==> Installing PHP dependencies"
composer install --optimize-autoloader --no-dev

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Building Laravel caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Restarting queue workers"
php artisan queue:restart

echo "==> Deployment completed"
