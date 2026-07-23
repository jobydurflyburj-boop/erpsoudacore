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

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_and_update_the_company_profile(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create(['is_default' => true, 'legal_name' => 'Old Name']);
        $role = Role::factory()->for($tenant)->create(['code' => Role::COMPANY_OWNER]);
        $role->permissions()->attach(Permission::whereIn('name', ['admin.view', 'admin.edit'])->pluck('id'));

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($owner);

        $show = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/admin/company-profile');
        $show->assertOk()->assertJsonPath('data.legal_name', 'Old Name');

        $update = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson('/api/v1/admin/company-profile', [
                'legal_name' => 'New Name',
                'legal_name_ar' => 'اسم جديد',
                'currency' => 'SAR',
                'language' => 'ar',
            ]);

        $update->assertOk()->assertJsonPath('data.legal_name', 'New Name');
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'legal_name' => 'New Name']);
    }

    public function test_a_role_without_admin_edit_cannot_update_the_profile(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create(['is_default' => true]);
        $role = Role::factory()->for($tenant)->create(['code' => Role::EMPLOYEE]); // no admin.* grants

        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson('/api/v1/admin/company-profile', ['legal_name' => 'Hacked Name']);

        $response->assertStatus(403);
    }
}
