#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

echo "==> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

echo "==> Installing Node dependencies..."
npm ci --no-audit --no-fund

echo "==> Building frontend assets..."
npm run build

echo "==> Caching framework files..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Creating storage symlink..."
php artisan storage:link || true

echo "==> Deploy script complete."
