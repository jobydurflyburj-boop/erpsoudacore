<?php

namespace Tests\Feature\PlatformAdmin;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithTenant(): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::COMPANY_OWNER]);
        $role->permissions()->attach(
            Permission::whereIn('name', ['admin.view', 'admin.create', 'admin.edit', 'admin.delete'])->pluck('id')
        );

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        return [$tenant, $owner];
    }

    public function test_owner_can_create_a_custom_role_with_a_specific_permission_set(): void
    {
        [$tenant, $owner] = $this->ownerWithTenant();
        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/admin/roles', [
            'code' => 'branch_supervisor',
            'name_en' => 'Branch Supervisor',
            'name_ar' => 'مشرف الفرع',
            'permissions' => ['admin.view', 'dashboard.view'],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('roles', ['code' => 'branch_supervisor', 'tenant_id' => $tenant->id, 'is_system_role' => false]);

        $roleId = $response->json('data.id');
        $granted = collect($response->json('data.permissions'))->pluck('name');
        $this->assertEqualsCanonicalizing(['admin.view', 'dashboard.view'], $granted->all());
    }

    public function test_permission_catalog_is_grouped_by_module_for_the_role_builder_ui(): void
    {
        [$tenant, $owner] = $this->ownerWithTenant();
        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/admin/permissions');

        $response->assertOk();
        $this->assertArrayHasKey('admin', $response->json('data'));
        $this->assertArrayHasKey('dashboard', $response->json('data'));
        $this->assertArrayHasKey('core', $response->json('data'));
    }

    public function test_updating_a_custom_roles_permissions_replaces_the_grant_set(): void
    {
        [$tenant, $owner] = $this->ownerWithTenant();
        Sanctum::actingAs($owner);

        $create = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/admin/roles', [
            'code' => 'limited_role', 'name_en' => 'Limited', 'permissions' => ['admin.view'],
        ]);
        $roleId = $create->json('data.id');

        $update = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->putJson("/api/v1/admin/roles/{$roleId}/permissions", ['permissions' => ['core.view', 'core.export']]);

        $update->assertOk();
        $granted = collect($update->json('data.permissions'))->pluck('name');
        $this->assertEqualsCanonicalizing(['core.view', 'core.export'], $granted->all());
    }

    public function test_a_custom_role_in_one_tenant_cannot_be_seen_by_another_tenant(): void
    {
        [$tenantA, $ownerA] = $this->ownerWithTenant();
        [$tenantB] = $this->ownerWithTenant();

        Sanctum::actingAs($ownerA);

        $create = $this->withHeader('X-Tenant-ID', $tenantA->id)->postJson('/api/v1/admin/roles', [
            'code' => 'tenant_a_only', 'name_en' => 'Tenant A Only', 'permissions' => ['admin.view'],
        ]);
        $roleId = $create->json('data.id');

        // Same user attempts to read it while the request resolves Tenant B
        // instead — BindAuthenticatedTenant already rejects the mismatch
        // (see TENANT_ISOLATION_REVIEW.md Finding 4) before this route
        // model binding would even run.
        $response = $this->withHeader('X-Tenant-ID', $tenantB->id)->getJson("/api/v1/admin/roles/{$roleId}");

        $response->assertStatus(403);
    }
}
