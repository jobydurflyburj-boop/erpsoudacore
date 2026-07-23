<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
    }

    public function test_a_company_can_register_and_receives_an_owner_account(): void
    {
        $response = $this->postJson('/api/v1/public/tenants/register', [
            'legal_name' => 'Acme Trading Co.',
            'subdomain' => 'acme',
            'admin_full_name' => 'Ahmed Al-Otaibi',
            'admin_email' => 'ahmed@acme.test',
            'admin_password' => 'a-strong-unique-passphrase',
            'admin_password_confirmation' => 'a-strong-unique-passphrase',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('tenants', ['subdomain' => 'acme', 'status' => 'trial']);

        $tenant = Tenant::where('subdomain', 'acme')->firstOrFail();

        $owner = User::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('email', 'ahmed@acme.test')
            ->first();

        $this->assertNotNull($owner);
        $this->assertEquals('company_owner', $owner->role->code);
        $this->assertNull($owner->email_verified_at); // must verify before full access
    }

    public function test_registration_fails_with_a_duplicate_subdomain(): void
    {
        Tenant::factory()->create(['subdomain' => 'taken']);

        $response = $this->postJson('/api/v1/public/tenants/register', [
            'legal_name' => 'Someone Else LLC',
            'subdomain' => 'taken',
            'admin_full_name' => 'Sara Al-Qahtani',
            'admin_email' => 'sara@else.test',
            'admin_password' => 'another-strong-passphrase',
            'admin_password_confirmation' => 'another-strong-passphrase',
        ]);

        $response->assertStatus(422);
    }
}
