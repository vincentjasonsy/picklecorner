#!/bin/sh
set -e
cd /var/www/html

export PORT="${PORT:-8080}"

# Writable dirs (Render uses ephemeral disk by default)
chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true

# SQLite file DB (default path); ensure file exists and is writable by php-fpm
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi
chown www-data:www-data database/database.sqlite 2>/dev/null || true

# Create users, sessions, cache, jobs, and app tables (required for SESSION_DRIVER=database, etc.)
php artisan migrate --force --no-interaction

php artisan storage:link --force 2>/dev/null || true

php artisan package:discover --ansi 2>/dev/null || true

# Runtime caches once env (APP_KEY, DB_*, etc.) is injected by the platform
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Nginx listens on Render's PORT (or 8080 locally)
export PORT
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-available/default
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
