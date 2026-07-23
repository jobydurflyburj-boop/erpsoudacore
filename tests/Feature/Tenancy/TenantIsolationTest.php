<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Multitenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The single most important guarantee in the whole foundation: a user in
 * Tenant A can never see or touch a row belonging to Tenant B — verified
 * at both the application-scope layer AND, separately, via a raw query
 * that bypasses Eloquent's global scope entirely, to prove PostgreSQL RLS
 * itself is what's actually holding the line, not just application code
 * remembering to filter.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_users_index_request_never_returns_another_tenants_users(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        [$tenantA, $userA, $roleA] = $this->makeTenantOwner('tenant-a');
        [$tenantB, $userB] = $this->makeTenantOwner('tenant-b');

        Sanctum::actingAs($userA);

        $response = $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->getJson('/api/v1/admin/users');

        $response->assertOk();

        $emails = collect($response->json('data'))->pluck('email');

        $this->assertTrue($emails->contains($userA->email));
        $this->assertFalse($emails->contains($userB->email));
    }

    public function test_row_level_security_blocks_cross_tenant_reads_even_without_the_eloquent_scope(): void
    {
        [$tenantA, $userA] = $this->makeTenantOwner('rls-a');
        [$tenantB, $userB] = $this->makeTenantOwner('rls-b');

        // Bind the Postgres session to Tenant A directly, then run a RAW
        // query (bypassing Eloquent's global scope entirely) for Tenant
        // B's user row. If RLS is doing its job, this returns zero rows
        // even though the query itself doesn't filter by tenant_id.
        app(TenantContext::class)->set($tenantA);
        app(TenantContext::class)->apply();

        $rows = DB::table('users')->where('id', $userB->id)->get();

        $this->assertCount(0, $rows, 'RLS failed to block a cross-tenant raw read.');

        app(TenantContext::class)->reset();
    }

    private function makeTenantOwner(string $subdomain): array
    {
        $tenant = Tenant::factory()->active()->create(['subdomain' => $subdomain]);
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::COMPANY_OWNER]);

        // Grant the seeded admin.view permission so the index route is reachable.
        $permission = \App\Models\Permission::where('name', 'admin.view')->first();
        if ($permission) {
            $role->permissions()->attach($permission->id);
        }

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'email' => "owner@{$subdomain}.test",
            'password' => Hash::make('irrelevant-for-this-test'),
        ]);

        return [$tenant, $user, $role];
    }
}
