# Database Verification Tool

Runs the real files in `database/migrations/` against a real PostgreSQL
database, without needing `composer install` — built specifically for
this project's sandbox, where Packagist is unreachable (HTTP 403,
outside the network policy's allowed domains) and the full Laravel
framework therefore cannot be installed.

`schema_shim.php` implements the minimal subset of `Schema`/`Blueprint`/
`DB` that this project's migrations actually call (enumerated by
grepping every migration file first, not guessed) — enough to execute
the migration files **verbatim**, so this tests the real code, not a
paraphrase of it. It is not a Laravel replacement and makes no attempt
to be one beyond what these 39 migration files need.

## Usage

```bash
# One-time setup (adjust for your Postgres install):
createdb soudacore_verify
psql -c "CREATE USER soudacore WITH PASSWORD 'secret';"
psql -c "CREATE DATABASE soudacore_verify OWNER soudacore;"
psql -c "GRANT ALL PRIVILEGES ON DATABASE soudacore_verify TO soudacore;"

php tools/db-verify/run_migrations.php
```

Environment variables `VERIFY_DB_HOST`, `VERIFY_DB_NAME`,
`VERIFY_DB_USER`, `VERIFY_DB_PASS` override the connection defaults.

## A gotcha worth knowing about

If you run this more than once against the same database without
dropping it first, `DROP DATABASE` will fail silently-ish if there's a
leftover open connection (e.g. a `psql` session still attached) —
you'll see spurious "relation already exists" failures on the second
run that have nothing to do with the migrations themselves. Terminate
connections first:

```sql
SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'soudacore_verify';
DROP DATABASE IF EXISTS soudacore_verify;
```

This happened once during this tool's own first use — see
`docs/DATABASE_VERIFICATION.md` for the full account, including that it
was correctly identified as a connection-cleanup artifact, not a real
migration bug, by re-running cleanly and getting 39/39 again.

## What this does NOT verify

Application logic, HTTP behavior, Eloquent model events (`Auditable`,
global scopes), and the PHPUnit suite all still require the real
framework and are out of scope for this tool. See
`docs/DATABASE_VERIFICATION.md` for the precise boundary of what was
and wasn't verified using it.
