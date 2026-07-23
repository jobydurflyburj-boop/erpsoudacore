# Database Verification Sprint

Selected as the "next unfinished sprint" per `ROADMAP.md`'s own ranking
— the Runtime Verification Sprint was listed first among "Next sprint"
candidates, with the roadmap's own text arguing it should be prioritized
after four consecutive feature sprints each independently found real
bugs only visible by reading code carefully. This sprint is the first
attempt to actually act on that.

## What was and wasn't achievable

**`composer install` cannot run in this sandbox.** Verified directly:
`https://repo.packagist.org/packages.json` returns HTTP 403 — outside
this environment's network allowlist, not a project defect. Without
Packagist, the Laravel framework itself cannot be installed, which means
the application/HTTP layer (Controllers, Services, Middleware pipeline,
Eloquent model events, the full PHPUnit suite) genuinely cannot be
executed here. That remains true after this sprint — it isn't solved,
it's a hard environment constraint that needs a different environment
(one with real internet access) to lift.

**What *is* achievable, and was done for real:** PostgreSQL itself has
no such dependency. This sprint installed a real PostgreSQL 16 instance
and built a minimal, faithful Schema/Blueprint/DB compatibility shim
(`tools/db-verify/`, now a permanent part of this repo) that runs the
**actual migration files, verbatim** — not hand-transcribed copies —
against real Postgres. This tests the database layer for real, which is
also the single highest-risk layer in the project (every tenant-
isolation guarantee this codebase makes lives here).

---

## Results

### 1. All 39 migrations run cleanly against real PostgreSQL

```
=== 39/39 migrations ran cleanly, 0 failed ===
```

Confirmed twice (once during initial development of the tool, once
again from its final location in the repo, after a false-alarm retry
traced to a leftover open connection rather than a real bug — see
`tools/db-verify/README.md`). This alone is meaningful: it's the first
time in this project's history that the schema has been proven to
actually build.

### 2. Row-Level Security is enabled on exactly the right tables

29 tables created; 26 have `RLS ENABLED` + `FORCE`d. The 3 without are
`tenants`, `permissions`, and `personal_access_tokens` — exactly the
three tables documented everywhere as intentionally platform-level or
framework-owned, not tenant data. No table is missing RLS that should
have it, and none has it that shouldn't.

### 3. Cross-tenant read isolation — proven empirically, not just reasoned about

Inserted a lead for "Tenant B" as superuser, then connected as the
**actual application role** (`soudacore`, not a superuser), bound the
session to Tenant A via `set_config('app.tenant_id', ...)` — exactly
what `TenantContext::apply()` does — and ran a raw query:

```
visible_rows_of_tenant_b_lead: 0
```

Flipping `app.is_super_admin` to `true` on the same connection made the
same row visible:

```
visible_as_super_admin: 1
```

This is the exact proof `TenantIsolationTest` and
`CrossTenantTokenTest` assert in code — now also demonstrated against a
real database instance.

### 4. `WITH CHECK` write protection — proven empirically

Attempted an `INSERT` claiming to belong to Tenant B while the session
was bound to Tenant A:

```
ERROR:  new row violates row-level security policy for table "lead_statuses"
```

The equivalent same-tenant insert succeeded normally. This directly
validates the exact mechanism `TENANT_ISOLATION_REVIEW.md`'s Findings 1
and 2 were about (registration inserting rows before `TenantContext` was
bound to the new tenant, and Super Admin role provisioning inserting a
`tenant_id IS NULL` row without the `is_super_admin` flag set) — both of
those fixes exist specifically so the real `WITH CHECK` behavior just
demonstrated doesn't reject legitimate application writes.

### 5. The Super Admin platform-level insert fix — proven empirically

Inserting a `tenant_id IS NULL` role without `is_super_admin` set:
`ERROR: new row violates row-level security policy for table "roles"`.
With it set: succeeds. This is `RoleProvisioningService::
provisionSuperAdminRole()`'s exact fix, now verified against real RLS
rather than only traced through code.

### 6. `SequenceService`'s hand-written raw SQL — proven correct

The exact `INSERT ... ON CONFLICT (tenant_id, name) DO NOTHING` +
`UPDATE ... SET next_value = next_value + 1 ... RETURNING next_value - 1`
sequence from `SequenceService::next()` was run twice against a real
counter row: returned `1`, then `2`. Atomic per-tenant numbering
confirmed to actually work, not just parse.

### 7. `citext` case-insensitive uniqueness — proven correct

Inserting `Owner@Example.com` then `owner@example.com` for the same
tenant correctly raised a unique-constraint violation — the
`ALTER TABLE users ALTER COLUMN email TYPE citext` step in the
foundation migration behaves exactly as intended.

### 8. The Super Admin partial unique index — proven correct

Two `tenant_id IS NULL` users with the same email correctly collide on
`users_platform_email_unique` — the fix for the gap noted in that
migration's own comment (`tenant_id, email` uniqueness alone doesn't
catch NULL-tenant duplicates in SQL) is confirmed working.

---

## Something worth documenting more clearly, found along the way (not a bug)

`failed_login_attempts`' RLS policy allows a `tenant_id IS NULL` write
from any session (a failed login against an unresolvable subdomain has
no tenant to attribute it to), but its `USING` clause — unchanged from
every other table's standard policy — means that row can then only ever
be **read back by a Super Admin session**, never by the session that
wrote it. Tested directly: the row inserts successfully, is invisible
immediately afterward to the same non-super-admin session, and becomes
visible once `is_super_admin` is set.

On reflection this is correct, not a bug: a failed login with no
resolvable tenant genuinely isn't any tenant's data, so restricting its
visibility to the platform level is the right call — it just wasn't
stated explicitly anywhere before. The originally-used path (a tenant's
*own* failed logins, `tenant_id` set) was also verified directly and
works exactly as expected: visible to that tenant's own session,
invisible to others. No code change made; the migration's existing
comment could be extended to state this read-asymmetry explicitly for
the next person who reads it, but that's a documentation nice-to-have,
not a fix.

---

## What remains unverified, and why this sprint doesn't close the gap entirely

Application logic: whether `LeadService::create()` actually calls
`SequenceService` correctly, whether `AuthService`'s password check
actually works end-to-end, whether the `Auditable` trait's model events
actually fire the way the code says, whether all 91 API endpoints
actually route and respond as designed, and the entire 27-file PHPUnit
suite — **none of this can be exercised without the real Laravel
framework**, and that remains blocked by this sandbox's Packagist
restriction. `tools/db-verify/` is a permanent addition to this repo
specifically so this database-layer check can be re-run cheaply in
future sprints, in this environment or any other — but the next
genuinely complete verification step still requires an environment with
real internet access to run `composer install` once.
