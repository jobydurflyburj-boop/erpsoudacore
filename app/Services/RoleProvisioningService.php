<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Support\Collection;

/**
 * The single place that knows what "the default roles" are and what each
 * one can do out of the box. Called once at tenant registration
 * (RegistrationService) to give every new company a working RBAC setup
 * immediately — and reused by database/seeders/RoleSeeder for local/dev
 * seeding, so the two never drift apart.
 *
 * Every role created here is fully editable afterward via RoleService —
 * this is a starting point, not a hardcoded ceiling (see
 * docs/FOUNDATION.md "RBAC").
 */
class RoleProvisioningService
{
    /**
     * module => action => [role codes granted this permission]
     * Only 'admin' and 'core' modules exist today (see
     * config/permissions.php) — this matrix is deliberately small because
     * business modules (CRM, Sales, ...) don't exist yet in this
     * foundation. Adding a module later means adding its grants here.
     */
    private function defaultGrantMatrix(): array
    {
        return [
            'admin.view' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::HR],
            'admin.create' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],
            'admin.edit' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],
            'admin.delete' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER],
            'core.view' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],
            'core.export' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::ACCOUNTANT],
            // Every default role sees the dashboard — what data appears
            // on it is further narrowed by the widgets themselves (e.g.
            // an Employee's dashboard omits company-wide financials),
            // not by this permission. This gate is just "can you open
            // the dashboard route at all".
            'dashboard.view' => [
                Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER,
                Role::SALES, Role::ACCOUNTANT, Role::HR, Role::INVENTORY,
                Role::CASHIER, Role::EMPLOYEE,
            ],
            // CRM: Sales sees/edits leads assigned to them (enforced by
            // LeadPolicy's ownership check on top of this base grant, not
            // a separate permission) — Owner/Admin/Manager see and manage
            // everything. See docs/CRM_MODULE.md "Record-level scoping".
            'crm.view' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::SALES],
            'crm.create' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::SALES],
            'crm.edit' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::SALES],
            'crm.delete' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER],
            'crm.export' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER],

            // Inventory: Owner/Admin/Manager/Inventory role manage it; Sales/Cashier can view only (need it to build sales documents).
            'inventory.view' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::INVENTORY, Role::SALES, Role::CASHIER, Role::ACCOUNTANT],
            'inventory.create' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::INVENTORY],
            'inventory.edit' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::INVENTORY],
            'inventory.delete' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],
            'inventory.export' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER],

            // Purchase: Owner/Admin/Manager/Inventory manage it.
            'purchase.view' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::INVENTORY, Role::ACCOUNTANT],
            'purchase.create' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::INVENTORY],
            'purchase.edit' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::INVENTORY],
            'purchase.delete' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],
            'purchase.approve' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER],
            'purchase.export' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER],

            // Sales: Owner/Admin/Manager/Sales/Cashier — the customer-facing document chain.
            'sales.view' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::SALES, Role::CASHIER, Role::ACCOUNTANT],
            'sales.create' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::SALES, Role::CASHIER],
            'sales.edit' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::SALES],
            'sales.delete' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],
            'sales.approve' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER],
            'sales.export' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::ACCOUNTANT],
            'sales.print' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::SALES, Role::CASHIER],

            // Accounting: Owner/Admin/Accountant only — financial records are the most sensitive module.
            'accounting.view' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::ACCOUNTANT],
            'accounting.create' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ACCOUNTANT],
            'accounting.edit' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ACCOUNTANT],
            'accounting.export' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::ACCOUNTANT],

            // Reports: every management-tier role can view; export narrower.
            'reports.view' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::ACCOUNTANT, Role::SALES, Role::INVENTORY],
            'reports.export' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::ACCOUNTANT],
            // Custom Report Builder / Scheduled Reports — Owner/Admin/Manager can build and schedule; everyone above can still view/export the built-in reports.
            'reports.create' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER],
            'reports.edit' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER],
            'reports.delete' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],

            // AI Assistant: available to everyone with a real seat — it's a personal productivity tool, not a sensitive-data module.
            'ai.view' => [
                Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER,
                Role::SALES, Role::ACCOUNTANT, Role::HR, Role::INVENTORY, Role::CASHIER, Role::EMPLOYEE,
            ],
            // Settings/prompt templates/dismissing suggestions are configuration, not personal-productivity use — Owner/Admin only.
            'ai.edit' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],
            'ai.delete' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],

            // HR & Payroll: Owner/Admin/HR manage it fully; Manager can view and approve
            // (leave/overtime for their own team) but not touch payroll runs/payslips directly.
            'hr_payroll.view' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::HR, Role::MANAGER],
            'hr_payroll.create' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::HR],
            'hr_payroll.edit' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::HR],
            'hr_payroll.delete' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN],
            'hr_payroll.approve' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::HR, Role::MANAGER],
            'hr_payroll.export' => [Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::HR],

            // Employee Self-Service: every default role — anyone with a
            // login may also have an Employee record and want to see
            // their own attendance/leave/payslips (scoped server-side to
            // their own employee row, not gated by role at all beyond
            // "has a real seat").
            'ess.view' => [
                Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::SALES,
                Role::ACCOUNTANT, Role::HR, Role::INVENTORY, Role::CASHIER, Role::EMPLOYEE,
            ],
            'ess.create' => [
                Role::SUPER_ADMIN, Role::COMPANY_OWNER, Role::ADMIN, Role::MANAGER, Role::SALES,
                Role::ACCOUNTANT, Role::HR, Role::INVENTORY, Role::CASHIER, Role::EMPLOYEE,
            ],
        ];
    }

    private function roleLabels(): array
    {
        return [
            Role::COMPANY_OWNER => ['en' => 'Company Owner', 'ar' => 'مالك الشركة'],
            Role::ADMIN => ['en' => 'Admin', 'ar' => 'مدير النظام'],
            Role::MANAGER => ['en' => 'Manager', 'ar' => 'مدير'],
            Role::SALES => ['en' => 'Sales', 'ar' => 'المبيعات'],
            Role::ACCOUNTANT => ['en' => 'Accountant', 'ar' => 'محاسب'],
            Role::HR => ['en' => 'HR', 'ar' => 'الموارد البشرية'],
            Role::INVENTORY => ['en' => 'Inventory', 'ar' => 'المخزون'],
            Role::CASHIER => ['en' => 'Cashier', 'ar' => 'أمين الصندوق'],
            Role::EMPLOYEE => ['en' => 'Employee', 'ar' => 'موظف'],
        ];
    }

    /**
     * @return Collection<Role> the newly created roles, keyed by code
     */
    public function provisionDefaultRoles(Tenant $tenant): Collection
    {
        $permissions = Permission::all()->keyBy('name');
        $grants = $this->defaultGrantMatrix();
        $created = collect();

        foreach ($this->roleLabels() as $code => $label) {
            $role = Role::create([
                'tenant_id' => $tenant->id,
                'code' => $code,
                'name_en' => $label['en'],
                'name_ar' => $label['ar'],
                'is_system_role' => true,
            ]);

            $permissionIds = collect($grants)
                ->filter(fn (array $roleCodes) => in_array($code, $roleCodes, true))
                ->keys()
                ->map(fn (string $permissionName) => $permissions->get($permissionName)?->id)
                ->filter()
                ->values()
                ->all();

            if (! empty($permissionIds)) {
                // Explicit pivot tenant_id — sync() with bare IDs writes
                // NULL for any extra pivot column, and role_permissions.
                // tenant_id is RLS-protected (tenant_id = current_tenant_id()
                // OR is_super_admin()). A NULL tenant_id is invisible to
                // every normal tenant session, which would make every
                // permission grant silently disappear for real users —
                // see docs/FOUNDATION.md "Tenant isolation — pivot writes".
                $role->permissions()->sync(
                    collect($permissionIds)->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tenant->id]])->all()
                );
            }

            $created->put($code, $role);
        }

        return $created;
    }

    /**
     * The platform-level Super Admin role is provisioned once, globally
     * (tenant_id = null), not per-tenant — see the roles migration's note
     * on NULL tenant_id meaning "platform role".
     *
     * This row's tenant_id is NULL, so RLS's WITH CHECK
     * (tenant_id = current_tenant_id() OR is_super_admin()) can only be
     * satisfied via is_super_admin() — a NULL tenant_id can never equal
     * current_tenant_id(), even when no tenant is bound. We deliberately
     * flip that session flag for the duration of this write: it's a
     * fixed, backend-controlled operation (provisioning code, not a
     * user-suppliable path), so bypassing RLS for exactly this insert is
     * safe — same reasoning as AuthService::attemptSuperAdminLogin's
     * credential lookup.
     */
    public function provisionSuperAdminRole(): Role
    {
        $context = app(\App\Multitenancy\TenantContext::class);
        $context->setSuperAdmin(true);
        $context->apply();

        $role = Role::withoutTenantScope()->firstOrCreate(
            ['tenant_id' => null, 'code' => Role::SUPER_ADMIN],
            ['name_en' => 'Super Admin', 'name_ar' => 'المشرف العام', 'is_system_role' => true]
        );

        $role->permissions()->sync(
            Permission::all()->mapWithKeys(fn (Permission $p) => [$p->id => ['tenant_id' => null]])->all()
        );

        return $role;
    }
}
