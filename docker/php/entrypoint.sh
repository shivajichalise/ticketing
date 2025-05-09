#!/bin/sh
set -e

echo "Preparing Laravel environment..."

# Link storage (safe to ignore if already linked)
php artisan storage:link || true

# Ensure writable permissions
chmod -R ug+rw storage bootstrap/cache || true

# Clear & rebuild caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Apply any outstanding migrations
php artisan migrate:fresh --seed --force

echo "Laravel is ready."

# Run PHP-FPM (or whatever CMD is passed)
exec "$@"
