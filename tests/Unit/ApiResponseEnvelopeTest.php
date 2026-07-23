<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression test for a real bug found while building the CRM dashboard:
 * Controller::ok() had an `is_array($data) ? $data : ['data' => $data]`
 * special case that silently skipped the {"data": ...} envelope for
 * every plain-array response — which was most message-only responses
 * (login, password reset, etc.) and both composite dashboard payloads.
 * Fixed to always wrap; this test pins the corrected behavior for both
 * a message-only response and an array-of-named-keys response so it
 * can't regress silently again.
 */
class ApiResponseEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_message_only_response_is_wrapped_in_the_data_envelope(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('CorrectPassword!123'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->postJson('/api/v1/auth/change-password', [
            'current_password' => 'CorrectPassword!123',
            'new_password' => 'BrandNewPassword!456',
            'new_password_confirmation' => 'BrandNewPassword!456',
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
        $this->assertArrayHasKey('message', $response->json('data'));
    }

    public function test_a_composite_array_response_is_wrapped_in_the_data_envelope(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::COMPANY_OWNER]);
        $role->permissions()->attach(\App\Models\Permission::where('name', 'dashboard.view')->value('id'));

        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/dashboard');

        $response->assertOk();
        // Top-level keys must be exactly {"data": {...}} — NOT
        // {"widgets": ..., "charts": ...} at the root, which is what the
        // bug produced.
        $this->assertEqualsCanonicalizing(['data'], array_keys($response->json()));
        $this->assertArrayHasKey('widgets', $response->json('data'));
    }
}
