#!/bin/sh

set -e  # Exit if any command fails

# Run Laravel Artisan commands
echo "Running Laravel Artisan commands and composer update ..."

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan migrate --force
# php artisan queue:table

# php artisan migrate:f
# php artisan db:seed

# php artisan dynamics:sync-warehouses
# php artisan dynamics:sync-categories
# php artisan dynamics:sync-technicians
# php artisan sync:warehouse-stock
# php artisan technicians:sync-stock
# php artisan sync:technician-transfers

echo "Artisan commands executed successfully!"


echo "Fixing storage permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache


# Start s6-overlay to manage Nginx & PHP-FPM
exec /init
