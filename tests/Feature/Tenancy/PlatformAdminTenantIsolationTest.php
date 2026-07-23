<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Notification as NotificationModel;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Multitenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Extends the foundation's TenantIsolationTest to the tables added by the
 * Platform Administration module — every new RLS-protected table gets
 * the same raw-query proof the foundation established.
 */
class PlatformAdminTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantUser(string $subdomain): array
    {
        $tenant = Tenant::factory()->active()->create(['subdomain' => $subdomain]);
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        return [$tenant, $user];
    }

    public function test_tasks_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA, $userA] = $this->makeTenantUser('tasks-a');
        [$tenantB, $userB] = $this->makeTenantUser('tasks-b');

        Task::create([
            'tenant_id' => $tenantB->id, 'assigned_to_user_id' => $userB->id,
            'created_by_user_id' => $userB->id, 'title' => 'Tenant B secret task',
        ]);

        app(TenantContext::class)->set($tenantA);
        app(TenantContext::class)->apply();

        $rows = DB::table('tasks')->where('title', 'Tenant B secret task')->get();

        $this->assertCount(0, $rows);

        app(TenantContext::class)->reset();
    }

    public function test_notifications_are_invisible_across_tenants_via_the_api(): void
    {
        [$tenantA, $userA] = $this->makeTenantUser('notif-a');
        [$tenantB, $userB] = $this->makeTenantUser('notif-b');

        NotificationModel::create([
            'tenant_id' => $tenantB->id, 'user_id' => $userB->id,
            'category' => 'task.assigned', 'title' => 'Tenant B notification',
        ]);

        Sanctum::actingAs($userA);

        $response = $this->withHeader('X-Tenant-ID', $tenantA->id)->getJson('/api/v1/notifications');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_company_settings_are_isolated_per_tenant(): void
    {
        [$tenantA, $userA] = $this->makeTenantUser('settings-a');
        [$tenantB] = $this->makeTenantUser('settings-b');

        $companyB = Company::where('tenant_id', $tenantB->id)->firstOrFail();

        \App\Models\CompanySetting::create([
            'tenant_id' => $tenantB->id, 'company_id' => $companyB->id,
            'key' => 'week_start_day', 'value' => 'sunday',
        ]);

        app(TenantContext::class)->set($tenantA);
        app(TenantContext::class)->apply();

        $rows = DB::table('company_settings')->where('key', 'week_start_day')->get();

        $this->assertCount(0, $rows);

        app(TenantContext::class)->reset();
    }
}
