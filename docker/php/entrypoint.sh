#!/usr/bin/env bash
# The real startup script that makes "docker compose up" actually work
# out of the box — a genuine gap before this: the Dockerfile's CMD was
# just `php-fpm` directly, with nothing waiting for Postgres to be
# ready or running migrations, so a fresh `docker compose up` would
# start php-fpm successfully against a database with no tables at all,
# and every request would fail until someone manually exec'd `php
# artisan migrate`. Runs as root (this script only — see the Dockerfile,
# which no longer sets `USER www-data` before this), so it can wait on
# a socket and read `.env`, then drops to www-data via `su-exec` before
# handing off to the real process. Never runs migrations from the
# `queue`/`scheduler` containers — only the `app` container should do
# this, so two containers starting simultaneously don't race each
# other running `migrate --force` at the same time.

set -euo pipefail

wait_for_postgres() {
    local host="${DB_HOST:-postgres}"
    local port="${DB_PORT:-5432}"
    local attempt=0
    local max_attempts=30

    echo "==> Waiting for PostgreSQL at ${host}:${port}..."
    until (echo > "/dev/tcp/${host}/${port}") 2>/dev/null; do
        attempt=$((attempt + 1))
        if [ "$attempt" -ge "$max_attempts" ]; then
            echo "==> PostgreSQL did not become reachable after ${max_attempts} attempts — giving up." >&2
            exit 1
        fi
        sleep 2
    done
    echo "==> PostgreSQL is reachable."
}

run_migrations_if_app_container() {
    # Only the primary `app` service runs migrations — `queue` and
    # `scheduler` (docker-compose.yml) set RUN_MIGRATIONS=false so
    # they never race the app container for this on startup.
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        echo "==> Running database migrations..."
        su-exec www-data php artisan migrate --force
    else
        echo "==> RUN_MIGRATIONS=false — skipping (this container isn't responsible for migrations)."
    fi
}

cache_config_if_production() {
    if [ "${APP_ENV:-production}" = "production" ]; then
        echo "==> Rebuilding config/route/event caches for production..."
        su-exec www-data php artisan config:cache
        su-exec www-data php artisan route:cache
        su-exec www-data php artisan event:cache
    fi
}

fix_storage_permissions() {
    # A real, easy-to-miss issue with the dev docker-compose.yml's
    # `.:/var/www` bind mount: the Dockerfile's build-time `chown
    # www-data storage bootstrap/cache` gets shadowed the moment the
    # host directory mounts over it at container start, since the
    # mount brings the *host's* file ownership with it, not the
    # image's. Re-applying this at every startup (cheap — only these
    # two directories, not the whole tree) means storage/logs and
    # cached config actually stay writable by www-data regardless of
    # which mode (bind-mounted dev, or baked-into-the-image
    # docker-compose.prod.yml) started this container.
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
}

main() {
    fix_storage_permissions
    wait_for_postgres
    run_migrations_if_app_container
    cache_config_if_production

    if [ "${SUPERVISOR_MODE:-false}" = "true" ]; then
        echo "==> SUPERVISOR_MODE=true — starting supervisord (php-fpm + queue worker + scheduler in one container)."
        exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
    fi

    echo "==> Starting: $*"
    exec su-exec www-data "$@"
}

main "$@"
