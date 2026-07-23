#!/usr/bin/env bash
# Production Readiness — a real rollback path. Rolls back to a given
# git ref (tag or commit SHA), reinstalls dependencies for that ref,
# rebuilds caches, and restarts services — the same sequence
# scripts/deploy.sh uses, applied to a prior known-good ref instead of
# HEAD. Deliberately does NOT roll back database migrations
# automatically — running `migrate:rollback` blind, without knowing
# whether the migrations since the target ref are safe to reverse
# (some in this project explicitly are not — see e.g. the Accounting
# module's reversal-entry migrations, which are additive by design),
# is a real risk of data loss greater than the deploy issue being
# rolled back from. A human reviews and runs any needed migration
# rollback explicitly.
#
# Usage: bash scripts/rollback.sh <git-ref>

set -euo pipefail

if [ $# -ne 1 ]; then
    echo "Usage: $0 <git-ref>" >&2
    exit 1
fi

TARGET_REF="$1"

echo "==> Rolling back to ${TARGET_REF}: $(date -u +%Y-%m-%dT%H:%M:%SZ)"

git fetch origin
git checkout "${TARGET_REF}"

composer install --no-dev --optimize-autoloader --no-interaction

php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

if command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl restart soudacore-app:* || true
elif command -v docker >/dev/null 2>&1; then
    docker compose -f docker-compose.yml -f docker-compose.prod.yml restart app
fi

php artisan queue:restart

echo "==> Rolled back to ${TARGET_REF}. Review docs/BACKUP_RESTORE_GUIDE.md if a database rollback is also needed."
