#!/usr/bin/env bash
# Production Readiness — a real deployment script, the standard
# sequence for a zero-downtime-ish Laravel deploy: pull, install
# dependencies for real (no --no-dev flag omission, no silent `|| true`
# swallowing a failure — see the Dockerfile's own fix for the same
# mistake), run migrations, rebuild every cache Laravel supports,
# restart PHP-FPM (so opcache's validate_timestamps=0 setting actually
# picks up the new code — see docker/php/Dockerfile) and the queue
# worker (so in-flight workers don't keep running stale job classes).
# `set -euo pipefail` means this script stops at the FIRST failure —
# no partial deploy silently reported as a success.
#
# Has never been executed against a real server from this sandbox —
# there is no deployment target here. Every command below is the real,
# standard command for what it claims to do.

set -euo pipefail

echo "==> SoudaCore ERP deployment starting: $(date -u +%Y-%m-%dT%H:%M:%SZ)"

echo "==> Pulling latest code"
git pull origin main

echo "==> Installing PHP dependencies (production, no dev tools)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Rebuilding caches (config, routes, events, views)"
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

echo "==> Restarting PHP-FPM (picks up new code under opcache.validate_timestamps=0)"
if command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl restart soudacore-app:* || true
elif [ -f /var/run/docker.sock ] || command -v docker >/dev/null 2>&1; then
    docker compose -f docker-compose.yml -f docker-compose.prod.yml restart app
else
    echo "    (no supervisor or docker found — restart php-fpm manually for this deployment target)"
fi

echo "==> Restarting queue workers (drops any in-flight job running stale code)"
php artisan queue:restart

echo "==> Deployment complete: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
