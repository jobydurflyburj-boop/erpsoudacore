#!/usr/bin/env bash
# Production Readiness — a real, first-time setup script for a fresh
# environment. Mirrors docs/INSTALLATION_GUIDE.md's manual steps
# exactly (this script and that doc must not drift apart) so a real
# operator can either read the guide and type each command, or just
# run this once. Idempotent where practical: re-running after a
# partial failure doesn't re-generate an already-set APP_KEY.

set -euo pipefail

echo "==> SoudaCore ERP installation starting"

if [ ! -f .env ]; then
    echo "==> Creating .env from .env.example"
    cp .env.example .env
else
    echo "==> .env already exists — leaving it as-is"
fi

echo "==> Installing PHP dependencies"
composer install --no-interaction

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "==> Generating APP_KEY"
    php artisan key:generate
else
    echo "==> APP_KEY already set — skipping"
fi

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Creating the storage symlink"
php artisan storage:link || true

echo ""
echo "==> Installation complete."
echo "    Next: create your first tenant via POST /api/v1/public/tenants/register"
echo "    (see docs/INSTALLATION_GUIDE.md for the full request body and next steps)."
