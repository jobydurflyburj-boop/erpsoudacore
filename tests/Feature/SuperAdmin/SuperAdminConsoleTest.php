<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RegistrationService;
use App\Services\RoleProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $role = app(RoleProvisioningService::class)->provisionSuperAdminRole();

        return User::withoutGlobalScope('tenant')->create([
            'tenant_id' => null,
            'role_id' => $role->id,
            'email' => 'super@platform.test',
            'password' => Hash::make('a-strong-unique-passphrase'),
            'full_name' => 'Platform Admin',
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
    }

    private function registerTenant(string $subdomain): array
    {
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Console Test Co',
            'subdomain' => $subdomain,
            'admin_full_name' => 'Owner Person',
            'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_a_non_super_admin_is_forbidden_from_the_platform_console(): void
    {
        [$tenant, $owner] = $this->registerTenant('console-forbidden');

        Sanctum::actingAs($owner);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/v1/admin/platform/tenants')
            ->assertStatus(403);
    }

    public function test_super_admin_can_list_tenants_across_the_whole_platform(): void
    {
        [$tenantA] = $this->registerTenant('console-list-a');
        [$tenantB] = $this->registerTenant('console-list-b');

        $superAdmin = $this->makeSuperAdmin();
        Sanctum::actingAs($superAdmin);

        $response = $this->getJson('/api/v1/admin/platform/tenants');

        $response->assertOk();
        $subdomains = collect($response->json('data'))->pluck('subdomain');
        $this->assertTrue($subdomains->contains('console-list-a'));
        $this->assertTrue($subdomains->contains('console-list-b'));
    }

    public function test_platform_metrics_report_real_counts(): void
    {
        $this->registerTenant('console-metrics-a');
        $this->registerTenant('console-metrics-b');

        $superAdmin = $this->makeSuperAdmin();
        Sanctum::actingAs($superAdmin);

        $response = $this->getJson('/api/v1/admin/platform/metrics');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, $response->json('data.tenants.total'));
        $this->assertGreaterThanOrEqual(2, $response->json('data.users_total'));
        $this->assertCount(6, $response->json('data.signups_last_6_months'));
    }

    public function test_suspending_a_tenant_blocks_its_users_from_logging_in(): void
    {
        [$tenant, $owner] = $this->registerTenant('console-suspend');

        $superAdmin = $this->makeSuperAdmin();
        Sanctum::actingAs($superAdmin);

        $suspend = $this->postJson("/api/v1/admin/platform/tenants/{$tenant->id}/suspend", [
            'reason' => 'Non-payment of invoice #123',
        ]);

        $suspend->assertOk()->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id, 'status' => 'suspended', 'suspension_reason' => 'Non-payment of invoice #123',
        ]);

        // The tenant's own login must now be rejected.
        $login = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/auth/login', [
            'email' => $owner->email,
            'password' => 'a-strong-unique-passphrase',
        ]);

        $login->assertStatus(422);
    }

    public function test_suspending_a_tenant_revokes_existing_sessions(): void
    {
        [$tenant, $owner] = $this->registerTenant('console-revoke');

        Sanctum::actingAs($owner);
        $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/me')->assertOk();

        $superAdmin = $this->makeSuperAdmin();
        Sanctum::actingAs($superAdmin);
        $this->postJson("/api/v1/admin/platform/tenants/{$tenant->id}/suspend", ['reason' => 'test'])->assertOk();

        $this->assertDatabaseHas('refresh_tokens', ['user_id' => $owner->id]);
        $this->assertDatabaseMissing('refresh_tokens', ['user_id' => $owner->id, 'revoked_at' => null]);
    }

    public function test_suspending_one_tenant_does_not_affect_another(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('console-isolate-a');
        [$tenantB, $ownerB] = $this->registerTenant('console-isolate-b');

        $superAdmin = $this->makeSuperAdmin();
        Sanctum::actingAs($superAdmin);

        $this->postJson("/api/v1/admin/platform/tenants/{$tenantA->id}/suspend", ['reason' => 'test'])->assertOk();

        $this->assertDatabaseHas('tenants', ['id' => $tenantA->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('tenants', ['id' => $tenantB->id, 'status' => 'trial']);

        $loginB = $this->withHeader('X-Tenant-ID', $tenantB->id)->postJson('/api/v1/auth/login', [
            'email' => $ownerB->email,
            'password' => 'a-strong-unique-passphrase',
        ]);

        $loginB->assertOk();
    }

    public function test_a_tenant_can_be_reactivated(): void
    {
        [$tenant, $owner] = $this->registerTenant('console-reactivate');

        $superAdmin = $this->makeSuperAdmin();
        Sanctum::actingAs($superAdmin);

        $this->postJson("/api/v1/admin/platform/tenants/{$tenant->id}/suspend", ['reason' => 'test'])->assertOk();

        $reactivate = $this->postJson("/api/v1/admin/platform/tenants/{$tenant->id}/reactivate");
        $reactivate->assertOk()->assertJsonPath('data.status', 'active');

        $login = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/auth/login', [
            'email' => $owner->email,
            'password' => 'a-strong-unique-passphrase',
        ]);
        $login->assertOk();
    }

    public function test_reactivating_a_non_suspended_tenant_is_rejected(): void
    {
        [$tenant] = $this->registerTenant('console-reactivate-invalid');

        $superAdmin = $this->makeSuperAdmin();
        Sanctum::actingAs($superAdmin);

        $response = $this->postJson("/api/v1/admin/platform/tenants/{$tenant->id}/reactivate");

        $response->assertStatus(409);
    }

    public function test_suspending_a_tenant_writes_to_that_tenants_own_activity_log(): void
    {
        [$tenant] = $this->registerTenant('console-activity');

        $superAdmin = $this->makeSuperAdmin();
        Sanctum::actingAs($superAdmin);

        $this->postJson("/api/v1/admin/platform/tenants/{$tenant->id}/suspend", ['reason' => 'test reason'])->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'tenant.suspended',
            'module' => 'platform',
        ]);
    }

    public function test_the_console_page_itself_is_reachable(): void
    {
        $response = $this->get('/super-admin');

        $response->assertOk();
        $response->assertSee('Super Admin Console');
    }
}
