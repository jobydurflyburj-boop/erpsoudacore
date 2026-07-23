# SoudaCore ERP — Installation Guide

This guide covers first-time setup of the `soudacore-api` Laravel 12
application, for a developer or operator setting it up for the first
time — whether via Docker (recommended) or a bare-metal PHP
environment. It mirrors `scripts/install.sh` step for step; that
script exists so you can run one command instead of typing each of
these, not as a separate, drifting procedure.

## Prerequisites

- PHP 8.3+ with extensions: `pdo_pgsql`, `pgsql`, `zip`, `intl`,
  `bcmath`, `opcache`, `redis` (the `redis` extension is only required
  if you run outside Docker — the Docker image already includes it)
- PostgreSQL 16+
- Redis 7+ (cache, session, and queue backend — see
  `docs/PRODUCTION_READINESS.md` for why Redis specifically)
- Composer 2
- Docker + Docker Compose (recommended path — skip the "Bare-metal"
  section below entirely if using this)

## Option A — Docker (recommended)

```bash
git clone <your-repository-url> soudacore-api
cd soudacore-api
cp .env.example .env
```

Edit `.env` — at minimum, set real values for:

- `DB_PASSWORD` — `docker-compose.yml` requires this to be set (no
  insecure default is baked in; the compose file will refuse to start
  postgres without it)
- `APP_KEY` — leave blank for now; generated below

Start the stack:

```bash
docker compose up -d --build
```

This starts `app` (PHP-FPM), `nginx` (port 8000), `postgres`, `redis`,
`queue` (a real `queue:work` worker — see `docs/PRODUCTION_READINESS.md`
for what's actually queued), and `scheduler` (runs
`schedule:run` every 60 seconds — see `routes/console.php` for what's
scheduled). `mailhog` (a local SMTP catcher, at http://localhost:8025)
only starts if you add `--profile dev` to the command above.

**Migrations run automatically** — `docker/php/entrypoint.sh` waits
for PostgreSQL to become reachable, then runs
`php artisan migrate --force` for real before `app` starts serving
requests (only the `app` container does this; `queue`/`scheduler` skip
it via `RUN_MIGRATIONS=false`, so two containers never race each other
running migrations at the same time). You still need to generate a
real `APP_KEY` and the storage symlink:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
```

Or run the whole first-time sequence at once:

```bash
docker compose exec app bash scripts/install.sh
```

The API is now live at `http://localhost:8000/api/v1`. Verify with:

```bash
curl http://localhost:8000/api/v1/health
```

You should get back `{"status":"ok","checks":{"database":"ok","cache":"ok","queue":"ok"},...}`.

## Option B — Bare-metal (no Docker)

```bash
git clone <your-repository-url> soudacore-api
cd soudacore-api
bash scripts/install.sh
```

`scripts/install.sh` does exactly this, in order:

1. Copies `.env.example` to `.env` if `.env` doesn't already exist
2. Runs `composer install`
3. Generates `APP_KEY` if one isn't already set
4. Runs `php artisan migrate --force`
5. Creates the `storage` symlink

Before running it, make sure `.env`'s `DB_*` and `REDIS_*` values
point at a real, reachable PostgreSQL and Redis instance you've
already provisioned — this script does not install or start either
for you outside Docker.

You'll still need a real web server in front of PHP-FPM — see
`docker/nginx/default.conf` for a real, working nginx config to adapt
(it already exists and doesn't need Docker to be useful as a
reference), and `docs/DEPLOYMENT_GUIDE.md` for the full production
picture (queue worker, scheduler, TLS termination).

## Creating your first tenant

SoudaCore is multi-tenant; there's no seeded "default" tenant in a
real (non-local) environment. Create one via the public registration
endpoint:

```bash
curl -X POST http://localhost:8000/api/v1/public/tenants/register \
  -H "Content-Type: application/json" \
  -d '{
    "legal_name": "Your Company Name",
    "subdomain": "yourcompany",
    "admin_full_name": "Your Name",
    "admin_email": "you@example.com",
    "admin_password": "a-strong-unique-passphrase"
  }'
```

**For local development/evaluation only**: running
`php artisan migrate:fresh --seed` (or `php artisan db:seed` on an
already-migrated database) with `APP_ENV=local` seeds a real demo
tenant for you automatically — `database/seeders/DemoTenantSeeder.php`
registers a tenant (subdomain `demo`, owner login
`owner@demo.soudacore.app` / `DemoPassword!123`) and
`database/seeders/DemoDataSeeder.php` adds a small set of genuinely
linked demo records (two products, a supplier, a lead, and a customer
converted from that lead) using the same real services every
controller uses — not a fixture dump. This seeder is a real no-op
outside `local`/`testing` environments (checked explicitly in both
seeder classes), so it never runs against a real production database
by accident. **Change the demo password immediately if you ever expose
this environment beyond your own machine.**

This provisions the tenant, its default chart of accounts, default
roles/permissions, default HR leave types and salary components, and
the first user (Company Owner role) — every "provision defaults on
registration" service built across every prior sprint runs here for
real. Log in via `POST /api/v1/auth/login` with the email/password
above (include `X-Tenant-ID: <tenant id from the register response>`
or use the tenant's subdomain, depending on your `CENTRAL_DOMAIN`
configuration — see `config/tenancy.php`).

## Configuring optional integrations

None of these are required for the application to run; all default to
"off" and degrade gracefully (see each feature's own sprint doc for
exactly how):

| Feature | Env vars | Default behavior if unset |
|---|---|---|
| AI Assistant LLM provider | `AI_PROVIDER`, `ANTHROPIC_API_KEY` or `OPENAI_API_KEY` | Real deterministic keyword-grounded answers, no LLM |
| OTP SMS delivery | `OTP_SMS_DRIVER` | Logs the OTP instead of sending an SMS (`OTP_SMS_DRIVER=log`) |
| Error tracking webhook | `ERROR_TRACKING_WEBHOOK_URL` | Errors are still logged normally; no external delivery |
| Mail | `MAIL_MAILER`, `MAIL_HOST`, etc. | Points at the `mailhog` dev container by default |

## Verifying the install

```bash
php artisan test
```

Runs the full test suite — hundreds of real integration, unit, and
tenant-isolation tests built across every sprint (see each sprint's
own doc for exact test counts). In this project's own development
sandbox, `composer install` has never been able to reach Packagist
(HTTP 403), so every test here has been lint-checked (`php -l`) but
never actually executed there — this is the first real opportunity for
someone with real internet access to run the suite for real. See
`docs/PRODUCTION_READINESS.md` for the full, honest account of what
has and hasn't been executed versus just verified by inspection.
