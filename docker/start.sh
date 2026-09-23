#!/bin/sh
# Container entrypoint: prepare Laravel, start the queue worker, then Apache.
set -e
cd /var/www/html

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan storage:link 2>/dev/null || true

# Anything created above (logs, compiled views) must be writable by Apache
chown -R www-data:www-data storage bootstrap/cache

# Queue worker for AI generation and document imports.
# Runs inside the web container because Render's free plan has no background workers.
(
    while true; do
        su -s /bin/sh www-data -c "php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600" || true
        sleep 2
    done
) &

exec apache2-foreground
