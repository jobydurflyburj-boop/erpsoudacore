<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Proves BindAuthenticatedTenant actually rejects a token being used
 * against the wrong tenant's subdomain/header — added after the tenant
 * isolation review found this was previously only an emergent side
 * effect of the global scope, not an explicit, tested guarantee.
 */
class CrossTenantTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_as_token_is_rejected_against_a_different_tenants_header(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenantA = Tenant::factory()->active()->create();
        $companyA = Company::factory()->for($tenantA)->create();
        $roleA = Role::factory()->for($tenantA)->create(['code' => Role::EMPLOYEE]);

        $userA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'company_id' => $companyA->id,
            'role_id' => $roleA->id,
            'password' => Hash::make('irrelevant'),
        ]);

        $tenantB = Tenant::factory()->active()->create();

        Sanctum::actingAs($userA);

        // User A's token, presented against Tenant B's header.
        $response = $this->withHeader('X-Tenant-ID', $tenantB->id)
            ->getJson('/api/v1/me');

        $response->assertStatus(403);
    }

    public function test_a_tenant_users_token_still_works_against_their_own_tenant(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::EMPLOYEE]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/me');

        $response->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_a_token_without_any_tenant_header_falls_back_to_the_users_own_tenant(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::EMPLOYEE]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($user);

        // No X-Tenant-ID header at all — BindAuthenticatedTenant must
        // still bind the user's own tenant rather than leaving the
        // request unscoped.
        $response = $this->getJson('/api/v1/me');

        $response->assertOk()->assertJsonPath('data.id', $user->id);
    }
}
