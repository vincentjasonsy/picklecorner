#!/bin/sh
set -e
cd /var/www/html

export PORT="${PORT:-8080}"

# Writable dirs (Render uses ephemeral disk by default)
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

php artisan storage:link --force 2>/dev/null || true

# Runtime caches once env (APP_KEY, DB_*, etc.) is injected by the platform
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Nginx listens on Render's PORT (or 8080 locally)
export PORT
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-available/default
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
