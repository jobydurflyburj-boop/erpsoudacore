<?php

namespace Tests\Feature\Rbac;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_role_without_admin_create_permission_cannot_invite_a_user(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();

        // 'employee' role: no admin.* grants at all in the default matrix.
        $role = Role::factory()->for($tenant)->create(['code' => Role::EMPLOYEE]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/admin/users', [
                'full_name' => 'New Hire',
                'email' => 'newhire@test.test',
                'role_id' => $role->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_granting_the_permission_allows_the_same_action(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => 'custom_hr']);

        $role->permissions()->attach(
            Permission::whereIn('name', ['admin.create', 'admin.view'])->pluck('id')
        );

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/admin/users', [
                'full_name' => 'New Hire',
                'email' => 'newhire2@test.test',
                'role_id' => $role->id,
            ]);

        $response->assertCreated();
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();

        $adminRole = Role::factory()->for($tenant)->create(['code' => Role::ADMIN, 'is_system_role' => true]);
        $adminRole->permissions()->attach(Permission::where('name', 'admin.delete')->value('id'));

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $adminRole->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($user);

        $targetRole = Role::factory()->for($tenant)->create(['is_system_role' => true]);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->deleteJson("/api/v1/admin/roles/{$targetRole->id}");

        $response->assertStatus(409);
    }
}
