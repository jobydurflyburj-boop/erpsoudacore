# SoudaCore ERP — Deployment Guide

Covers taking `soudacore-api` from a working local install
(`docs/INSTALLATION_GUIDE.md`) to a real production deployment, and
the ongoing deploy/rollback procedure after that.

## Production topology

```
                    ┌──────────────┐
  Internet ────────▶│    nginx     │  TLS termination happens here (or one layer
                    │ (docker/nginx│  further out, at a load balancer/CDN — either
                    │ /default.conf)│  way, nginx must see the real client IP via
                    └──────┬───────┘  X-Forwarded-For for trustProxies() to work)
                           │
                    ┌──────▼───────┐
                    │   app        │  PHP-FPM — serves every request
                    │  (php-fpm)   │
                    └──────┬───────┘
                           │
        ┌──────────────────┼──────────────────┐
        ▼                  ▼                  ▼
  ┌───────────┐     ┌───────────┐      ┌────────────┐
  │ postgres  │     │   redis   │      │   queue    │  a SEPARATE container/process
  │           │     │(cache/    │      │  (queue:    │  running `queue:work` — not
  │           │     │ session/  │      │   work)     │  the same process as `app`
  │           │     │  queue)   │      └────────────┘
  └───────────┘     └───────────┘      ┌────────────┐
                                         │ scheduler  │  a SEPARATE container/process
                                         │(schedule:  │  running `schedule:run` every
                                         │   run)     │  60s — see routes/console.php
                                         └────────────┘
```

Five real, separate running things in production, not just "the app":
nginx, PHP-FPM, a queue worker, a scheduler tick, and the database/
cache backend. `docker-compose.yml` + `docker-compose.prod.yml`
already define all five as separate services — this is not a
theoretical diagram, it's what those two files actually configure.

## Step 1 — Provision the server

Any host that can run Docker (or bare PHP 8.3 + PostgreSQL 16 + Redis
7, if not using Docker — see `docs/INSTALLATION_GUIDE.md`'s Option B).
Minimum realistic sizing for a small-to-medium tenant base: 2 vCPU,
4GB RAM, 40GB disk (more for Postgres storage depending on data
volume and backup retention — see `docs/BACKUP_RESTORE_GUIDE.md`).

## Step 2 — Secrets & environment management

**Never commit `.env`** — it's git-ignored already
(`.gitignore` confirmed to include `.env`). In production, set real
values via whatever your host provides for secrets — environment
variables injected by your orchestrator, a secrets manager (AWS
Secrets Manager, HashiCorp Vault, etc.) rendered into `.env` at deploy
time, or a `.env` file with `chmod 600` permissions readable only by
the deploy user. `.env.example` documents every variable this
application reads — treat any variable NOT in `.env.example` that
appears in your `.env` as a real question worth asking, and any
variable IN `.env.example` you haven't set as a real gap to fill
before going live (particularly `APP_KEY`, `DB_PASSWORD`, and
whichever mail/SMS/AI credentials you intend to actually use).

Required for a real production deployment, beyond what's needed for
local development:

- `APP_ENV=production`
- `APP_DEBUG=false` — **critical**: with this false,
  `bootstrap/app.php`'s exception handler never leaks a stack trace
  or raw exception message in a response (verified — see the
  catch-all `render()` callback's `app()->isProduction()` branch);
  with `APP_DEBUG=true` in production, that protection doesn't apply
- `APP_URL` — your real public URL
- A real `DB_PASSWORD` (not the `secret` placeholder in
  `docker-compose.yml`, which only exists so local `docker compose up`
  works out of the box)
- Real `MAIL_*` credentials pointed at an actual provider
- `ERROR_TRACKING_WEBHOOK_URL` if you want real error alerting beyond
  log files (see `docs/PRODUCTION_READINESS.md`)

## Step 3 — Storage, cache, logging, and queue configuration

Real config files exist for all four (added during the Final
Production Validation pass — previously these ran on Laravel's
unpublished internal defaults, which worked but weren't visible or
auditable in the repository itself):

- **`config/filesystems.php`** — `local`/`public` disks, plus a real
  `s3` disk definition (works with any S3-compatible provider, not
  AWS-exclusive — set `AWS_ENDPOINT` for DigitalOcean Spaces, MinIO,
  Cloudflare R2, etc.) ready to configure for off-host backup
  replication (see `docs/BACKUP_RESTORE_GUIDE.md`). Not wired to
  anything by default — set `FILESYSTEM_DISK=s3` and the `AWS_*`
  vars only if you actually use it.
- **`config/cache.php`** — `CACHE_STORE=redis` (the real default this
  project has used since Foundation) with a real, non-empty
  `CACHE_PREFIX` to avoid key collisions with anything else sharing
  the same Redis instance.
- **`config/logging.php`** — a real `stderr` channel, the standard
  practice for this project's containerized deployment
  (`docker-compose.yml`): container runtimes capture stdout/stderr
  directly, which survives container restarts/rebuilds better than a
  log file path inside a possibly-ephemeral container filesystem. Set
  `LOG_CHANNEL=stderr` in production if your log aggregation pulls
  from container output; leave `LOG_CHANNEL=stack` (the default) to
  keep writing to `storage/logs/laravel.log` as well.
- **`config/queue.php`** — `QUEUE_CONNECTION=redis` (the real default
  since Foundation), with `failed_jobs` wired as the real recovery
  path for jobs that exhaust their retries (added in the Production
  Readiness sprint — see `docs/PRODUCTION_READINESS.md`).

## Step 4 — Alternative: single-container deployment (supervisor)

The default topology (see "Production topology" above) runs nginx,
PHP-FPM, the queue worker, and the scheduler as four separate
containers via `docker-compose.yml` — the better default for anything
that supports multi-container orchestration (independent scaling,
independent restarts, one crashed process doesn't take the others
down). For a host that only supports a single container (some simple
VPS/PaaS setups), `docker/supervisor/supervisord.conf` runs all three
PHP processes (php-fpm, `queue:work`, a `schedule:run` loop) inside
one container instead — set `SUPERVISOR_MODE=true` and start just the
`app` service:

```bash
docker compose run -e SUPERVISOR_MODE=true --rm app
# or, to run it as the long-lived container:
docker compose up -d --scale queue=0 --scale scheduler=0 app
# with SUPERVISOR_MODE=true set in .env
```

`docker/php/entrypoint.sh` still runs first either way (waiting for
Postgres, running migrations for the `app` container) — supervisor
mode changes what happens *after* that, not the startup sequence
itself. You still need a separate nginx (or other reverse proxy)
pointed at this container's port 9000 — supervisor manages the PHP
processes, not the web server in front of them.

## Step 5 — Build and start

```bash
git clone <your-repository-url> /var/www/soudacore-api
cd /var/www/soudacore-api
cp .env.example .env
# edit .env with real production values

docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan event:cache
```

`docker-compose.prod.yml` (see the file itself for the full,
commented rationale) removes the dev-only source volume mounts (code
comes from the built image, not a live host mount — this is what
makes `opcache.validate_timestamps=0` in `docker/php/Dockerfile` safe),
stops Postgres/Redis from publishing ports to the host at all, and
never starts `mailhog`.

## Step 6 — TLS

This project's nginx config (`docker/nginx/default.conf`) terminates
plain HTTP on port 80. Put a real TLS-terminating layer in front of it
— a managed load balancer, Certbot + a second nginx/Caddy layer, or a
CDN — and ensure `trustProxies(at: '*')` (already configured in
`bootstrap/app.php`) is scoped down to your actual proxy's IP range in
a real deployment rather than left as a wildcard, so `$request->secure()`
(which `SecurityHeaders` middleware uses to decide whether to send
`Strict-Transport-Security`) reflects the real connection correctly.

## Step 7 — Ongoing deploys

```bash
bash scripts/deploy.sh
```

Or, wired into CI/CD: `.github/workflows/ci.yml`'s `deploy` job runs
this same script over SSH automatically on every push to `main` that
passes the test job — see that file for the real (if unexecuted in
this project's own development sandbox — no git remote exists here)
GitHub Actions configuration, including the `DEPLOY_HOST`/`DEPLOY_USER`/
`DEPLOY_SSH_KEY` secrets it expects to be configured as GitHub
Environment secrets, never committed.

`scripts/deploy.sh` runs, in order, and stops at the first failure
(`set -euo pipefail`): git pull, `composer install --no-dev
--optimize-autoloader`, `migrate --force`, rebuild every cache
(config/route/event/view), restart PHP-FPM, restart the queue worker
(`queue:restart` — lets in-flight jobs finish on old code, new jobs
pick up the new code, rather than killing jobs mid-flight).

## Rolling back

```bash
bash scripts/rollback.sh <git-tag-or-commit-sha>
```

Rolls the application code back to a prior ref and restarts services
— it deliberately does **not** roll back database migrations
automatically (see the script's own comment for why: several
migrations in this project, e.g. the Accounting module's reversal-entry
migrations, are additive by design and reversing them blind risks more
data loss than the deploy issue being rolled back from). Review
`docs/BACKUP_RESTORE_GUIDE.md` if the rollback genuinely needs a
database-level rollback too.

## Zero-downtime notes

This deployment topology (separate nginx/app/queue/scheduler
processes) supports a real rolling restart if your host supports it
(e.g. `docker compose up -d --no-deps --build app` while `nginx`
keeps serving from the still-running old container until the new one
passes its health check — see the `healthcheck:` block on `app` in
`docker-compose.yml`), but `scripts/deploy.sh` as written does a
simple restart, not a blue-green swap. For a deployment that cannot
tolerate the few seconds of `php-fpm` restart, extend
`scripts/deploy.sh` with your orchestrator's real rolling-update
mechanism (Kubernetes `Deployment` rolling update, ECS service
deployment, etc.) — this project's scope stops at giving you a real,
correct single-server deploy script, not a full orchestration layer.
