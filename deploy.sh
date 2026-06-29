#!/usr/bin/env bash
#
# Production deploy / update script for the SoLion VPN admin panel.
# Run on the VPS from the project root, e.g.:  bash deploy.sh
#
# It pulls the latest code from GitHub and rebuilds everything the repo
# does NOT ship (vendor/, public/build/). Your .env is never touched.

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/solion}"
BRANCH="${BRANCH:-main}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.2-fpm}"
WEB_USER="${WEB_USER:-www-data}"

cd "$APP_DIR"
echo "==> Deploying $BRANCH in $APP_DIR"

# Optional: pause the app so users don't hit a half-updated state.
php artisan down --render="errors::503" || true
trap 'php artisan up || true' EXIT

echo "==> Pulling latest code"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

# Build front-end assets if Node is available; otherwise assume public/build
# was uploaded manually (or the site only uses the admin theme in public/assets).
if command -v npm >/dev/null 2>&1; then
  echo "==> Building front-end assets (vite)"
  npm ci
  npm run build
else
  echo "==> npm not found; skipping asset build (using existing public/build)"
fi

echo "==> Migrations"
php artisan migrate --force

echo "==> Caching config / routes / views"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Storage symlink + permissions"
php artisan storage:link || true
chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "==> Reloading PHP-FPM"
sudo systemctl reload "$PHP_FPM_SERVICE" || true

php artisan up || true
trap - EXIT
echo "==> Done."
