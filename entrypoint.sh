#!/bin/sh

echo "Starting backend container initialization..."

# Run database migrations automatically in production
echo "Running migrations..."
php artisan migrate --force

# Seed database with mock customers and campaigns only if database is empty
echo "Checking if database requires seeding..."
php artisan tinker --execute="if (\App\Models\Customer::count() === 0) { echo 'Seeding database...\n'; \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]); } else { echo 'Database already has data. Skipping seed.\n'; }"

echo "Container initialization complete. Starting Apache..."

# Start Apache in the foreground (passed from Dockerfile)
exec apache2-foreground
