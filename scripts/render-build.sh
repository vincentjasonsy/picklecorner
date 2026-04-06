#!/usr/bin/env bash
# Optional: set this as the Render "Build Command" for a *non-Docker* PHP web service
# so Vite runs and creates public/build (that folder is not committed).
# Requires Node/npm available in the build environment.
set -euo pipefail

composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [[ -f package-lock.json ]]; then
  npm ci
  npm run build
else
  echo "package-lock.json missing; run npm install locally and commit the lockfile." >&2
  exit 1
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
