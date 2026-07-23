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

class BranchDepartmentTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithCompany(): array
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

        return [$tenant, $company, $owner];
    }

    public function test_full_branch_lifecycle_with_gps_and_working_hours(): void
    {
        [$tenant, $company, $owner] = $this->ownerWithCompany();
        Sanctum::actingAs($owner);

        $create = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/admin/branches', [
            'company_id' => $company->id,
            'name' => 'Riyadh Branch',
            'city' => 'Riyadh',
            'phone' => '0501234567',
            'working_hours' => ['sun' => ['open' => '09:00', 'close' => '18:00']],
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $create->assertCreated();
        $branchId = $create->json('data.id');
        $this->assertDatabaseHas('branches', ['id' => $branchId, 'city' => 'Riyadh']);

        $update = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/v1/admin/branches/{$branchId}", ['is_active' => false]);
        $update->assertOk()->assertJsonPath('data.is_active', false);

        $delete = $this->withHeader('X-Tenant-ID', $tenant->id)->deleteJson("/api/v1/admin/branches/{$branchId}");
        $delete->assertStatus(204);
        $this->assertSoftDeleted('branches', ['id' => $branchId]);
    }

    public function test_department_lifecycle_with_manager_assignment(): void
    {
        [$tenant, $company, $owner] = $this->ownerWithCompany();
        Sanctum::actingAs($owner);

        $create = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/admin/departments', [
            'company_id' => $company->id,
            'name_en' => 'Finance',
            'name_ar' => 'المالية',
            'description' => 'Handles accounting and payments',
            'manager_user_id' => $owner->id,
        ]);

        $create->assertCreated()->assertJsonPath('data.name_en', 'Finance');
        $this->assertDatabaseHas('departments', ['name_en' => 'Finance', 'manager_user_id' => $owner->id]);
    }

    public function test_a_branch_cannot_reference_another_tenants_company(): void
    {
        [$tenant, , $owner] = $this->ownerWithCompany();
        $otherTenant = Tenant::factory()->active()->create();
        $otherCompany = Company::factory()->for($otherTenant)->create();

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/admin/branches', [
            'company_id' => $otherCompany->id, // belongs to a different tenant
            'name' => 'Cross Tenant Branch',
        ]);

        $response->assertStatus(422); // fails the Rule::exists(...)->where('tenant_id', ...) check
    }
}
