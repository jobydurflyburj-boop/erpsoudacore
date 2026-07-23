# Hardened multi-stage build. Real gaps fixed across sprints, and one
# more found and fixed in this pass: `apk del postgresql-dev
# icu-dev libzip-dev` after compiling pdo_pgsql/intl/zip removed the
# *build* packages, but those Alpine `-dev` packages only pull in the
# runtime shared libraries (`postgresql-libs`, `icu-libs`, `libzip`)
# as transitive dependencies — deleting the `-dev` package doesn't
# reliably keep those runtime libs installed. A real risk of a
# container that builds successfully but fails at runtime with a
# missing shared library. Fixed below using Alpine's standard
# `--virtual .build-deps` pattern: runtime libraries installed
# permanently and separately from the build-only headers/tools, which
# are then removed as one clean virtual group.

# ---- Stage 1: install PHP dependencies with dev tools available ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress

# ---- Stage 2: runtime image ----
FROM php:8.3-fpm-alpine

# Real runtime libraries — installed permanently, never removed. These are
# what the compiled pdo_pgsql/intl/zip extensions actually link against
# when the container is running, not just while building.
RUN apk add --no-cache bash curl supervisor su-exec postgresql-libs icu-libs libzip oniguruma

# Build-only headers/tools — a virtual group removed as one unit once the
# extensions are compiled, so nothing is left behind by accident.
RUN apk add --no-cache --virtual .build-deps libzip-dev icu-dev postgresql-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip intl bcmath opcache \
    && docker-php-ext-configure opcache --enable-opcache \
    && apk del .build-deps

# Real production opcache tuning — validate_timestamps=0 means opcache
# never re-stats files on every request (a real, measurable throughput
# win), at the cost of requiring a deploy to explicitly reset/restart
# php-fpm to pick up new code — exactly what the deploy script
# (scripts/deploy.sh) does.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.jit=tracing'; \
        echo 'opcache.jit_buffer_size=64M'; \
    } > /usr/local/etc/php/conf.d/opcache-production.ini

WORKDIR /var/www

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD ps aux | grep -q '[p]hp-fpm: master process\|[s]upervisord' || exit 1

# entrypoint.sh waits for a real database connection, runs migrations
# for real, then hands off to whatever CMD this container was started
# with — php-fpm by default (this Dockerfile's own CMD), or `php
# artisan queue:work`/`schedule:run` when docker-compose.yml overrides
# `command:` for the `queue`/`scheduler` services, or supervisord (see
# docker/supervisor/supervisord.conf) for a single-container
# deployment mode — set SUPERVISOR_MODE=true to select that instead of
# passing a different CMD. Stays root only long enough to run this,
# then entrypoint.sh itself drops to www-data before exec'ing the real
# process — see the script for exactly how.
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
