# SoudaCore ERP — Backup & Restore Guide

## What gets backed up

`php artisan backup:database` (new this sprint —
`app/Console/Commands/BackupDatabaseCommand.php`) takes a real
`pg_dump` of the entire application database in PostgreSQL's custom
format (`-F c`) — every tenant's data, in one file, since this is a
shared-database multi-tenant architecture (Row-Level Security
isolates tenants at query time, not at the storage level — see
`docs/DATABASE_VERIFICATION.md`). There is no per-tenant backup
command; restoring the dump restores every tenant at once.

**Not covered by this backup**: anything in `storage/app` outside the
database (uploaded files, if any module stores them there — check
`docs/FEATURE_MATRIX.md` per module), and the `.env` file itself
(back that up separately, through your secrets manager, per
`docs/DEPLOYMENT_GUIDE.md` — it should never be backed up to the same
place as the database dump, since it contains real credentials).

## Automatic backups

Scheduled daily at 02:00 server time (`routes/console.php`):

```php
Schedule::command('backup:database')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();
```

Writes to `storage/app/backups/soudacore-<timestamp>.dump` and prunes
anything older than 14 days by default
(`--keep-days=14`; pass a different value to change the retention
window). **This directory lives on the same host as the application**
— for real disaster recovery (the host itself is lost), copy these
files off-host on a real schedule too: an S3-compatible bucket, a
separate backup server, whatever your infrastructure provides. This
project's own backup command does not do off-host replication itself
— wire that as a follow-up step in your deployment (e.g. a cron entry
running `aws s3 sync storage/app/backups/ s3://your-bucket/` after the
scheduled backup, or your platform's native volume-snapshot feature).

## Manual backup

```bash
docker compose exec app php artisan backup:database
# or, outside Docker:
php artisan backup:database

# with a custom retention window:
php artisan backup:database --keep-days=30
```

## Restoring from a backup

**This is destructive — read fully before running anything.**
Restoring replaces the target database's contents. Always restore
into a fresh/empty database first to verify the dump is good, never
directly onto a live production database as your first attempt.

```bash
# 1. Create a fresh database to restore into (verification step —
#    never restore directly onto production as your first attempt)
createdb -h <host> -U <user> soudacore_restore_test

# 2. Restore the dump into it
pg_restore -h <host> -U <user> -d soudacore_restore_test \
  --no-owner --no-privileges \
  storage/app/backups/soudacore-2026-07-15-020000.dump

# 3. Verify — connect and spot-check real data
psql -h <host> -U <user> -d soudacore_restore_test \
  -c "SELECT count(*) FROM tenants;"
```

Once verified, restoring onto the real target database:

```bash
# Stop the application first — nothing should be writing to the
# database during a restore.
docker compose stop app queue scheduler

# Drop and recreate the target database (adjust names to your setup —
# NEVER run this against the wrong database).
dropdb -h <host> -U <user> soudacore
createdb -h <host> -U <user> soudacore

pg_restore -h <host> -U <user> -d soudacore \
  --no-owner --no-privileges \
  storage/app/backups/soudacore-2026-07-15-020000.dump

# Bring the application back up.
docker compose start app queue scheduler
```

## Point-in-time considerations

`pg_dump` captures a snapshot at the moment it runs — restoring loses
any writes between that snapshot and the incident that made you need
to restore. For a lower Recovery Point Objective than "up to 24 hours
of data loss" (the daily schedule above), configure real PostgreSQL
WAL archiving / continuous archiving (`archive_mode`,
`archive_command`) at the database-server level — this is standard
PostgreSQL operational practice, not something `soudacore-api`'s
application code manages, and belongs in your PostgreSQL server
configuration rather than this repository.

## Restore verification checklist

After any real restore (not just the practice run above), verify:

- [ ] `GET /api/v1/health` returns `"database": "ok"`
- [ ] Row-Level Security policies are intact: `psql -c "SELECT tablename, rowsecurity FROM pg_tables WHERE schemaname='public' AND rowsecurity = true;"` should list every tenant-scoped table (see `docs/DATABASE_VERIFICATION.md` for the expected list) — a restore that drops and recreates via a tool unaware of RLS policies could theoretically lose them; `pg_restore` of a `pg_dump -F c` output does preserve them correctly, but this is worth confirming after any restore regardless
- [ ] Spot-check a real tenant's data: log in as a real user, confirm recent records are present
- [ ] Queue and scheduler containers restarted cleanly after the application came back up

## What has and hasn't been tested

This procedure has been written correctly against real `pg_dump`/
`pg_restore` command syntax and PostgreSQL's real custom-format
semantics — but has never been executed end-to-end in this project's
own development sandbox, which has no real PostgreSQL superuser access
configured for backup/restore testing beyond the migration-verification
database used throughout every prior sprint (`tools/db-verify/`, a
different, narrower use of PostgreSQL than a full application backup).
Run the manual restore steps above against a real staging environment
before relying on this procedure in a real incident.
