# Tenant Isolation Review — Findings & Fixes

A systematic audit of every query path in the Tenancy + Auth + RBAC
foundation, done in response to an explicit review request. Method: (1)
list every model and confirm its scoping status, (2) grep every place the
tenant scope or RLS is deliberately bypassed and justify each one, (3)
trace the timing of when the Postgres session's `app.tenant_id` gets
bound relative to every write, (4) check what happens when an
authenticated token and the resolved tenant don't obviously agree.

Four real issues were found. Two were **fail-closed bugs** (would have
broken legitimate operations, not leaked data) but they're exactly the
kind of bug that gets "fixed" by someone loosening the security check
that caused them — worth catching now. Two were genuine **hardening
gaps** with real, if narrow, leak/enumeration exposure. All four are
fixed in this codebase as of this review; each fix has a dedicated test.

---

## Where enforcement actually lives (for reference)

1. **`ResolveTenant` middleware** (global, runs first on every request) —
   resolves the tenant from subdomain/header and calls
   `TenantContext::apply()`, which runs
   `SELECT set_config('app.tenant_id', ...)` against the DB session.
2. **PostgreSQL RLS** — every tenant-owned table has `FORCE ROW LEVEL
   SECURITY` and a policy requiring `tenant_id = current_tenant_id() OR
   is_super_admin()`. This is the real boundary — it holds even for raw
   `DB::table()` queries and even against the table owner role.
3. **`BelongsToTenant` trait** (app layer) — auto-filters and auto-fills
   `tenant_id` on Eloquent models. Convenience + correctness-of-intent;
   not the security boundary, RLS is.
4. **`BindAuthenticatedTenant` middleware** (new — see Finding 4) — runs
   right after `auth:sanctum`, confirms the authenticated token's own
   tenant matches whatever `ResolveTenant` found, or binds a fallback.
5. **`CheckPermission` middleware** — RBAC, a separate concern from
   tenancy, but runs in the same request pipeline.

`TenantIsolationTest` and `CrossTenantTokenTest` (both in
`tests/Feature/Tenancy/`) exercise all of the above directly, including
a raw-query test that bypasses Eloquent's scope on purpose to prove RLS
— not app code — is what actually blocks a cross-tenant read.

---

## Finding 1 (critical, fail-closed) — registration couldn't write its own rows

**What:** `RegistrationService` creates a `Tenant`, then in the same DB
transaction creates its `Company`, `Branch`, default `Role`s, and owner
`User` — all with `tenant_id` set to the newly-created tenant's ID. But
this request came in on the **central domain** (a brand-new company has
no subdomain to resolve yet — it doesn't exist until the first line of
the transaction runs). `ResolveTenant` middleware never bound a tenant to
the Postgres session, so `current_tenant_id()` was still empty when every
subsequent insert ran. RLS's `WITH CHECK (tenant_id = current_tenant_id()
OR is_super_admin())` would reject every one of them — **company
registration would fail outright at the database layer**, not leak
anything, but be completely broken.

**Fix:** `RegistrationService` now calls `TenantContext::set($tenant)` +
`->apply()` immediately after creating the `Tenant` row, before any
further writes in the same transaction — and resets the context after,
so it can't leak into whatever runs next in the same PHP process (queue
worker, seeder chain). See `RegistrationService::registerCompany`.

**Test:** `RegisterCompanyTest` already exercised this path; it would
have failed loudly (a DB exception) before this fix, on a real Postgres
connection with RLS actually enabled — the bug was latent because
earlier verification never ran the full request against real RLS.

---

## Finding 2 (critical, fail-closed) — the Super Admin platform role had the same problem

**What:** identical root cause to Finding 1.
`RoleProvisioningService::provisionSuperAdminRole()` creates a
`tenant_id IS NULL` `Role` row (by design — it's a platform role, not
tied to any tenant). RLS's `WITH CHECK` needs `is_super_admin()` true to
accept a NULL-tenant insert, and nothing set that flag during seeding.

**Fix:** the method now sets `TenantContext::setSuperAdmin(true)` +
`apply()` before writing — deliberately: this is fixed, backend-only
provisioning code, never a user-suppliable path, so bypassing RLS for
exactly this insert is safe. Same justification pattern already used by
`AuthService::attemptSuperAdminLogin`'s credential lookup.

---

## Finding 3 (critical, leak-adjacent) — permission grants were being written invisibly

**What:** the worst one. Every place that granted permissions to a role
called `$role->permissions()->sync($permissionIds)` with a bare array of
permission IDs. Eloquent's default pivot insert has no way to know
`role_permissions.tenant_id` needs a value, so it wrote `NULL`. Under
RLS, a `NULL` tenant_id is invisible to every normal tenant session
(`NULL = current_tenant_id()` is never true in SQL) — meaning **every
default role's permission grants would have been silently invisible to
the very tenant they were granted to.** `CheckPermission` would have
denied every RBAC-gated request for every tenant user, permanently.
Not a cross-tenant leak — worse in a different way: total, silent RBAC
failure that would have looked like "nothing works" in production with
no obvious cause.

**Fix:** every `sync()` call (`RoleProvisioningService::provisionDefaultRoles`,
`RoleProvisioningService::provisionSuperAdminRole`,
`RoleRepository::syncPermissions`) now passes `tenant_id` explicitly in
the pivot data, keyed off `$role->tenant_id` — which is always correct
regardless of whether `TenantContext` happens to be bound to that tenant
in the current request (it often isn't, e.g. during registration). Also
wired `Role::permissions()` through a proper `RolePermission` pivot model
(`->using(RolePermission::class)`) instead of an anonymous pivot, for
consistency and future auditability.

**Test:** `PermissionEnforcementTest::test_granting_the_permission_allows_the_same_action`
would have failed before this fix (the granted permission wouldn't have
been visible to `Role::hasPermission()` under real RLS).

---

## Finding 4 (hardening gap) — a token's tenant was never explicitly checked against the resolved tenant

**What:** `ResolveTenant` (resolves tenant from subdomain/header) runs
*before* Sanctum authentication (which resolves *who* is calling) in the
middleware pipeline — they were never explicitly cross-checked. In
practice, `BelongsToTenant`'s global scope plus RLS already prevented an
actual data leak (a mismatched lookup for the wrong tenant's user returns
zero rows, which Sanctum's guard treats as "token invalid"), but relying
on that as the *only* protection had two real problems:

1. It surfaces as a confusing 401 rather than an intentional, auditable
   403 — not a security hole, but not a deliberate guarantee either.
2. If a route is ever added under `auth:sanctum` **without**
   `tenant.active` (which requires a resolved tenant) — an easy mistake
   for a future contributor to make — and the request hits the central
   domain with no tenant header, `TenantContext` has no tenant bound at
   all. `BelongsToTenant`'s global scope deliberately no-ops when no
   tenant is bound (see that trait's own comment) — meaning any
   tenant-scoped **write** in that request could run with a missing or
   wrong `tenant_id`, not just a blocked read.

**Fix:** new `BindAuthenticatedTenant` middleware, in the pipeline
immediately after `auth:sanctum` and before `tenant.active`:
- If a tenant was resolved (subdomain/header) and it doesn't match the
  authenticated user's own `tenant_id` → explicit `403`.
- If no tenant was resolved at all → falls back to binding the
  authenticated user's own tenant, so no authenticated request ever runs
  genuinely unscoped.
- If the authenticated identity is a Super Admin (`tenant_id IS NULL`,
  `role.code = super_admin`) → binds `is_super_admin` instead, no tenant.

**Tests:** `CrossTenantTokenTest` — proves a token is rejected against
the wrong tenant's header, still works against its own, and falls back
correctly with no header at all.

---

## Finding 5 (hardening gap) — the OTP step accepted a raw user_id from an unauthenticated client

**What:** after the password step, `/auth/login` returned
`{"status":"otp_required","user_id":"<uuid>"}`, and the (unauthenticated)
`/auth/otp/verify` endpoint accepted that `user_id` back and looked it up
with `User::withoutGlobalScope('tenant')->findOrFail(...)`. The OTP code
itself was still required and rate-limited (5 attempts, 5-minute expiry),
so this was never a direct leak — but it made the endpoint an oracle:
anyone could probe arbitrary user IDs against it without having triggered
that user's login themselves, and the explicit tenant-scope bypass on a
client-supplied ID is exactly the pattern this review was looking for.

**Fix:** `/auth/login` now returns an opaque, single-use, 48-character
`ticket` instead of `user_id` (`OtpService::generateWithTicket`,
new `otp_codes.ticket_hash` column, stored hashed like every other
token in this codebase). `/auth/otp/verify` takes `ticket` + `code`
only — no user identifier is ever exposed to or accepted from the
client for this flow. The `withoutGlobalScope('tenant')` lookup inside
`OtpService::verifyByTicket` is now safe because it's keyed off a
cryptographically random ticket the caller cannot forge or enumerate,
not a guessable/enumerable ID.

**Test:** `OtpLoginTest` — confirms `user_id` never appears in the
response and the ticket isn't UUID-shaped (i.e. isn't just the user ID
in disguise).

---

## Also reviewed, no change needed

- **`TokenService::refresh()`'s `withoutTenantScope()` lookup by
  `token_hash`** — the hash is an 80-character random value with a unique
  DB constraint; explicit, safe, same pattern as the OTP ticket fix.
- **`RoleRepository::findByCode` / `forTenant`, `UserRepository::findByEmailForTenant`** —
  all bypass the Eloquent scope but immediately re-filter by an explicit
  `tenant_id` parameter supplied by trusted server-side code (the
  authenticated request's own tenant, or the tenant being registered),
  never by client-controlled input.
- **`EmailVerificationController`'s signed-URL lookup** — protected by
  Laravel's `signed` route middleware (the URL's `id`/`hash` can't be
  tampered with), and the link itself is only ever generated pointing at
  the correct tenant's own subdomain.
- **Sanctum's own `personal_access_tokens` table** has no `tenant_id`
  column and isn't RLS-protected — every access pattern in this codebase
  scopes it via the owning `User` relationship or an explicit `user_id`
  filter, never queried broadly. Flagged here as a design note for future
  contributors rather than changed.
- **`refresh_tokens` and `otp_codes`** now both have RLS (already did);
  **`failed_login_attempts` and `password_reset_tokens`** did not, and
  have been added in this review (new migration
  `extend_rls_to_login_and_reset_tables`) — closing a latent gap before
  any future endpoint queries them without remembering to filter by
  tenant, rather than waiting for that mistake to happen.
- **`AuditLogService`** was silently skipping audit entries for any
  platform-level (`tenant_id IS NULL`) row — e.g. a Super Admin editing
  the Super Admin role's permissions went unaudited. Fixed to record
  `tenant_id = NULL` rows properly; RLS then makes them visible only to a
  Super Admin session, which is the correct audience.

---

## What "no cross-tenant leakage" means after this review

Every tenant-owned table is RLS-protected, `FORCE`d even against the
table owner. Every deliberate scope bypass in the codebase is now either
(a) immediately re-filtered by a trusted, server-controlled value, or (b)
keyed by a cryptographically random, single-use token the caller cannot
forge. Every authenticated request has its tenant binding explicitly
verified or established, not left to an accidental side effect of the
global scope. `TenantIsolationTest` and `CrossTenantTokenTest` encode
these guarantees as tests that fail loudly if a future change breaks any
of them.
