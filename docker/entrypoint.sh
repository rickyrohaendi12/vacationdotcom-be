#!/bin/sh
set -e

echo "=== Starting Vacation.com Backend ==="

# Generate APP_KEY kalau belum ada
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

# Cache konfigurasi untuk production
echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan migration
echo "Running migrations..."
php artisan migrate --force

# Clear & optimize
php artisan optimize

echo "=== Starting services ==="
exec supervisord -c /etc/supervisord.conf
