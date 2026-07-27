<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Multitenancy\TenantContext;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

/**
 * Orchestrates everything that must exist before a new company can log in
 * and use the product: tenant, default company, main branch, the full
 * default role set (RoleProvisioningService), and the first user
 * (Company Owner). All in one DB transaction — a partially-created tenant
 * left behind by a mid-way failure is worse than the registration simply
 * failing outright.
 */
class RegistrationService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly RoleProvisioningService $roleProvisioning,
        private readonly CrmProvisioningService $crmProvisioning,
        private readonly AccountingProvisioningService $accountingProvisioning,
        private readonly HrPayrollProvisioningService $hrPayrollProvisioning,
        private readonly PasswordPolicyService $passwordPolicy,
        private readonly EmailVerificationService $emailVerification,
        private readonly ActivityLogService $activityLog,
        private readonly TenantContext $tenantContext,
    ) {}

    public function registerCompany(array $data): array
    {
        if ($this->tenants->subdomainExists($data['subdomain'])) {
            throw new InvalidArgumentException('This subdomain is already taken.');
        }

        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['legal_name'],
                'subdomain' => $data['subdomain'],
                'status' => 'trial',
                'default_locale' => $data['default_locale'] ?? 'ar',
                'default_currency' => 'SAR',
                'timezone' => 'Asia/Riyadh',
                'trial_ends_at' => now()->addDays((int) config('tenancy.trial_days')),
            ]);

            // CRITICAL: bind the Postgres session to the tenant we just
            // created, before writing a single tenant-owned row. This
            // request came in on the central domain (no subdomain to
            // resolve — the tenant didn't exist until the line above),
            // so ResolveTenant middleware never bound one. Without this,
            // every insert below has tenant_id = <this tenant> while
            // app.tenant_id is still empty, and RLS's WITH CHECK
            // (tenant_id = current_tenant_id() OR is_super_admin()) would
            // reject every one of them — registration would fail outright
            // at the database layer. See docs/FOUNDATION.md
            // "Tenant isolation — registration's RLS bootstrap".
            $this->tenantContext->set($tenant);
            $this->tenantContext->apply();

            $company = Company::create([
                'tenant_id' => $tenant->id,
                'legal_name' => $data['legal_name'],
                'trade_name' => $data['trade_name'] ?? null,
                'cr_number' => $data['cr_number'] ?? null,
                'vat_number' => $data['vat_number'] ?? null,
                'is_default' => true,
            ]);

            $branch = Branch::create([
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'name' => 'Main Branch',
                'is_main' => true,
                'is_active' => true,
            ]);

            \App\Models\Warehouse::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name' => 'Main Warehouse',
                'is_default' => true,
                'is_active' => true,
            ]);

            $roles = $this->roleProvisioning->provisionDefaultRoles($tenant);
            $ownerRole = $roles->get(Role::COMPANY_OWNER);

            $this->crmProvisioning->provisionDefaults($tenant);
            $this->accountingProvisioning->provisionDefaults($tenant);
            $this->hrPayrollProvisioning->provisionDefaults($tenant);

            // Defensive re-validation: RegisterCompanyRequest already
            // enforces this for the HTTP path, but this service is also
            // called directly by seeders/console commands that bypass
            // the Form Request — the policy must hold either way.
            \Illuminate\Support\Facades\Validator::make(
                ['password' => $data['admin_password']],
                ['password' => $this->passwordPolicy->rule()]
            )->validate();

            $owner = User::create([
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'default_branch_id' => $branch->id,
                'role_id' => $ownerRole->id,
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'full_name' => $data['admin_full_name'] ?? $data['legal_name'].' Owner',
                'preferred_locale' => $data['default_locale'] ?? 'ar',
                'status' => User::STATUS_ACTIVE,
                'password_changed_at' => now(),
            ]);

            $this->passwordPolicy->recordHistory($owner, $owner->password);
            $this->emailVerification->send($owner);
            $this->activityLog->record($owner, $tenant->id, 'tenant.registered', 'Company registered and owner account created.');

            $result = ['tenant' => $tenant, 'company' => $company, 'user' => $owner];

            // Only reset if this request didn't already have its own
            // tenant bound (it never does for registration — central
            // domain only) — never clobber an outer tenant context that
            // might belong to a different caller in a long-running
            // process (queue worker processing multiple jobs).
            $this->tenantContext->reset();

            return $result;
        });
    }

    /**
     * Runs only when a Super Admin approves a pending registration request
     * (see SuperAdmin\PendingRegistrationController::approve). This is the
     * ONLY place a tenant ID is ever assigned — public registration never
     * reaches this method directly. Mirrors registerCompany()'s
     * provisioning steps, but sources data from an already-validated,
     * already-hashed TenantRegistrationRequest row instead of a live
     * request, since the plaintext password is long gone by review time.
     *
     * Explicitly generates the tenant's UUID in PHP (Str::uuid()) rather
     * than letting Postgres's gen_random_uuid() column default supply it —
     * unlike a normal single-row create, this flow needs the tenant's ID
     * immediately, for every row created after it in this same
     * transaction, and Eloquent never reads a DB-generated default back
     * into the model for non-incrementing keys (see HasUuid).
     */
    public function approveRegistrationRequest(\App\Models\TenantRegistrationRequest $registrationRequest, User $approvedBy): array
    {
        return DB::transaction(function () use ($registrationRequest, $approvedBy) {
            $tenant = Tenant::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name' => $registrationRequest->legal_name,
                'subdomain' => $registrationRequest->subdomain,
                'status' => 'trial',
                'default_locale' => $registrationRequest->default_locale ?? 'ar',
                'default_currency' => 'SAR',
                'timezone' => 'Asia/Riyadh',
                'trial_ends_at' => now()->addDays((int) config('tenancy.trial_days')),
            ]);

            $this->tenantContext->set($tenant);
            $this->tenantContext->apply();

            $company = Company::create([
                'tenant_id' => $tenant->id,
                'legal_name' => $registrationRequest->legal_name,
                'trade_name' => $registrationRequest->trade_name,
                'cr_number' => $registrationRequest->cr_number,
                'vat_number' => $registrationRequest->vat_number,
                'is_default' => true,
            ]);

            $branch = Branch::create([
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'name' => 'Main Branch',
                'is_main' => true,
                'is_active' => true,
            ]);

            \App\Models\Warehouse::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name' => 'Main Warehouse',
                'is_default' => true,
                'is_active' => true,
            ]);

            $roles = $this->roleProvisioning->provisionDefaultRoles($tenant);
            $ownerRole = $roles->get(Role::COMPANY_OWNER);

            $this->crmProvisioning->provisionDefaults($tenant);
            $this->accountingProvisioning->provisionDefaults($tenant);
            $this->hrPayrollProvisioning->provisionDefaults($tenant);

            $owner = User::create([
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'default_branch_id' => $branch->id,
                'role_id' => $ownerRole->id,
                'email' => $registrationRequest->admin_email,
                'password' => $registrationRequest->admin_password_hash,
                'full_name' => $registrationRequest->admin_full_name,
                'preferred_locale' => $registrationRequest->default_locale ?? 'ar',
                'status' => User::STATUS_ACTIVE,
                'password_changed_at' => now(),
            ]);

            $this->emailVerification->send($owner);
            $this->activityLog->record($owner, $tenant->id, 'tenant.registered', 'Company registered and owner account created (approved by '.$approvedBy->email.').');

            $registrationRequest->update([
                'status' => 'approved',
                'tenant_id' => $tenant->id,
                'reviewed_by' => $approvedBy->id,
                'reviewed_at' => now(),
            ]);

            $this->tenantContext->reset();

            return ['tenant' => $tenant, 'company' => $company, 'user' => $owner];
        });
    }

}
