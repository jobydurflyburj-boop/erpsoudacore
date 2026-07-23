<?php

namespace Tests\Feature\PlatformAdmin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithCompany(): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::COMPANY_OWNER]);
        $role->permissions()->attach(
            Permission::whereIn('name', ['admin.view', 'admin.edit'])->pluck('id')
        );

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        return [$tenant, $company, $owner];
    }

    public function test_admin_reset_password_sends_a_reset_email_without_revealing_a_new_password(): void
    {
        Notification::fake();

        [$tenant, $company, $owner] = $this->ownerWithCompany();

        $employeeRole = Role::factory()->for($tenant)->create(['code' => Role::EMPLOYEE]);
        $target = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $employeeRole->id,
            'email' => 'target@resetme.test', 'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/admin/users/{$target->id}/reset-password");

        $response->assertOk();
        Notification::assertSentTo($target, \App\Notifications\PasswordResetNotification::class);
    }

    public function test_assigning_branches_to_a_user_is_tenant_scoped_in_the_pivot(): void
    {
        [$tenant, $company, $owner] = $this->ownerWithCompany();

        $branch1 = Branch::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id]);
        $branch2 = Branch::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id]);

        $employeeRole = Role::factory()->for($tenant)->create(['code' => Role::EMPLOYEE]);
        $target = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $employeeRole->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->putJson("/api/v1/admin/users/{$target->id}/branches", [
                'branch_ids' => [$branch1->id, $branch2->id],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('user_branches', [
            'user_id' => $target->id, 'branch_id' => $branch1->id, 'tenant_id' => $tenant->id,
        ]);
        $this->assertDatabaseHas('user_branches', [
            'user_id' => $target->id, 'branch_id' => $branch2->id, 'tenant_id' => $tenant->id,
        ]);
    }

    public function test_mfa_enabled_reflects_role_not_a_stale_flag(): void
    {
        [$tenant, $company, $owner] = $this->ownerWithCompany();
        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson("/api/v1/admin/users/{$owner->id}");

        $response->assertOk()->assertJsonPath('data.mfa_enabled', true); // Company Owner is in MFA_REQUIRED_ROLES
    }
}
