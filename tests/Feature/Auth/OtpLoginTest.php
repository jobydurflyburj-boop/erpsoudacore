<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_otp_step_never_exposes_a_raw_user_id(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create(['code' => Role::COMPANY_OWNER]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'email' => 'owner@otp-test.test',
            'password' => Hash::make('correct-horse-battery-staple'),
        ]);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/v1/auth/login', [
                'email' => 'owner@otp-test.test',
                'password' => 'correct-horse-battery-staple',
            ]);

        $response->assertOk()->assertJsonPath('status', 'otp_required');
        $response->assertJsonStructure(['ticket']);
        $response->assertJsonMissingPath('user_id');

        // The ticket must be an opaque, non-UUID-shaped random string —
        // not something that doubles as a lookup key on its own meaning.
        $this->assertDoesNotMatchRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $response->json('ticket')
        );
    }

    public function test_an_invalid_ticket_is_rejected_without_revealing_anything(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'ticket' => str_repeat('a', 48),
            'code' => '123456',
        ]);

        $response->assertStatus(422);
    }
}
