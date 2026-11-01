#!/bin/sh

set -e

echo "Waiting for PostgreSQL..."

until php artisan db:show --database=pgsql > /dev/null 2>&1; do
    sleep 2
done

echo "Running database migrations..."
php artisan migrate --force

echo "Seeding demo data..."
php artisan db:seed --force

echo "Clearing Laravel caches..."
php artisan optimize:clear

echo "Starting Laravel API..."
exec php artisan serve --host=0.0.0.0 --port=8000