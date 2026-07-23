<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantWithUser(string $roleCode = Role::EMPLOYEE, string $password = 'correct-horse-battery-staple'): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create(['subdomain' => 'login-test']);
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => $roleCode]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'email' => 'user@login-test.test',
            'password' => Hash::make($password),
        ]);

        return [$tenant, $user];
    }

    public function test_a_user_can_log_in_with_correct_credentials(): void
    {
        [$tenant, $user] = $this->makeTenantWithUser();

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/auth/login', [
                'email' => 'user@login-test.test',
                'password' => 'correct-horse-battery-staple',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'authenticated')
            ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in', 'user']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        [$tenant] = $this->makeTenantWithUser();

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/auth/login', [
                'email' => 'user@login-test.test',
                'password' => 'totally-wrong',
            ]);

        $response->assertStatus(422);
    }

    public function test_high_privilege_roles_require_otp_before_tokens_are_issued(): void
    {
        [$tenant] = $this->makeTenantWithUser(Role::COMPANY_OWNER);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/auth/login', [
                'email' => 'user@login-test.test',
                'password' => 'correct-horse-battery-staple',
            ]);

        $response->assertOk()->assertJsonPath('status', 'otp_required');
        $response->assertJsonMissing(['access_token']);
    }

    public function test_login_is_rejected_for_a_suspended_tenant(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->suspended()->create(['subdomain' => 'suspended-co']);
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::EMPLOYEE]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'email' => 'user@suspended.test',
            'password' => Hash::make('whatever-password'),
        ]);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/auth/login', [
                'email' => 'user@suspended.test',
                'password' => 'whatever-password',
            ]);

        $response->assertStatus(422);
    }
}
