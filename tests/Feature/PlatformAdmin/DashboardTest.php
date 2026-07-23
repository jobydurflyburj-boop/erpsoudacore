<?php

namespace Tests\Feature\PlatformAdmin;

use App\Models\Company;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithTenant(): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::COMPANY_OWNER]);
        $role->permissions()->attach(
            \App\Models\Permission::whereIn('name', ['dashboard.view', 'admin.view', 'admin.create', 'core.view'])->pluck('id')
        );

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        return [$tenant, $user];
    }

    public function test_dashboard_reports_deferred_widgets_honestly_instead_of_fake_numbers(): void
    {
        [$tenant, $user] = $this->ownerWithTenant();
        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/dashboard');

        $response->assertOk();
        $response->assertJsonPath('data.widgets.revenue.available', false);
        $response->assertJsonPath('data.widgets.revenue.module', 'accounting');
        $response->assertJsonPath('data.widgets.customers.available', false);
        $response->assertJsonPath('data.charts.monthly_revenue.available', false);
    }

    public function test_dashboard_employee_count_is_real(): void
    {
        [$tenant, $user] = $this->ownerWithTenant();
        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/dashboard');

        $response->assertJsonPath('data.widgets.employees.available', true);
        $response->assertJsonPath('data.widgets.employees.count', 1); // just the owner created above
    }

    public function test_dashboard_task_summary_reflects_real_tasks(): void
    {
        [$tenant, $user] = $this->ownerWithTenant();

        Task::factory()->create([
            'tenant_id' => $tenant->id, 'assigned_to_user_id' => $user->id, 'created_by_user_id' => $user->id,
        ]);
        Task::factory()->overdue()->create([
            'tenant_id' => $tenant->id, 'assigned_to_user_id' => $user->id, 'created_by_user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/dashboard');

        $response->assertJsonPath('data.widgets.tasks.pending', 2);
        $response->assertJsonPath('data.widgets.tasks.overdue', 1);
    }

    public function test_quick_actions_are_filtered_by_the_users_actual_permissions(): void
    {
        [$tenant, $owner] = $this->ownerWithTenant();
        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/dashboard');

        $keys = collect($response->json('data.quick_actions'))->pluck('key');
        $this->assertTrue($keys->contains('invite_user')); // owner has admin.create
    }

    public function test_a_role_without_dashboard_view_is_denied(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => 'no_dashboard_role']); // zero permissions

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/dashboard');

        $response->assertStatus(403);
    }
}
