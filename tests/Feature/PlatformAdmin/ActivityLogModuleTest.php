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

class ActivityLogModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_branch_writes_a_module_attributed_activity_log_entry(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::COMPANY_OWNER]);
        $role->permissions()->attach(
            Permission::whereIn('name', ['admin.create', 'core.view'])->pluck('id')
        );

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($owner);

        $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/admin/branches', [
            'company_id' => $company->id,
            'name' => 'Jeddah Branch',
        ])->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'module' => 'admin',
            'event' => 'admin.created',
        ]);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/activity-logs');
        $response->assertOk();
        $this->assertTrue(collect($response->json('data'))->contains(fn ($log) => $log['module'] === 'admin'));
    }

    public function test_a_password_change_never_leaks_the_hash_into_the_audit_trail(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('OldPassword!123'),
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/auth/change-password', [
            'current_password' => 'OldPassword!123',
            'new_password' => 'BrandNewPassword!456',
            'new_password_confirmation' => 'BrandNewPassword!456',
        ])->assertOk();

        $logs = \App\Models\AuditLog::where('auditable_id', $user->id)->get();

        foreach ($logs as $log) {
            $this->assertArrayNotHasKey('password', $log->new_values ?? []);
            $this->assertArrayNotHasKey('password', $log->old_values ?? []);
        }
    }

    public function test_export_streams_a_csv_of_activity_logs(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::COMPANY_OWNER]);
        $role->permissions()->attach(Permission::where('name', 'core.export')->value('id'));

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->get('/api/v1/activity-logs/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
