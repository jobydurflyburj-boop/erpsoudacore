<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\RefreshToken;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create();

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);
    }

    public function test_issuing_a_token_creates_a_matching_refresh_token_row(): void
    {
        $user = $this->makeUser();
        $service = app(TokenService::class);

        $result = $service->issue($user, Request::create('/'), rememberMe: false);

        $this->assertNotEmpty($result['access_token']);
        $this->assertNotEmpty($result['refresh_token']);
        $this->assertDatabaseHas('refresh_tokens', [
            'id' => $result['refresh_token_id'],
            'user_id' => $user->id,
            'remember_me' => false,
        ]);
    }

    public function test_refreshing_rotates_the_token_and_invalidates_the_old_one(): void
    {
        $user = $this->makeUser();
        $service = app(TokenService::class);

        $issued = $service->issue($user, Request::create('/'));
        $refreshed = $service->refresh($issued['refresh_token'], Request::create('/'));

        $this->assertNotEquals($issued['refresh_token'], $refreshed['refresh_token']);

        $original = RefreshToken::where('token_hash', hash('sha256', $issued['refresh_token']))->first();
        $this->assertNotNull($original->revoked_at);
    }

    public function test_reusing_an_already_rotated_refresh_token_revokes_the_whole_family(): void
    {
        $user = $this->makeUser();
        $service = app(TokenService::class);

        $issued = $service->issue($user, Request::create('/'));
        $service->refresh($issued['refresh_token'], Request::create('/')); // rotates once — old token now revoked

        $this->expectException(RuntimeException::class);
        $service->refresh($issued['refresh_token'], Request::create('/')); // reuse of the now-revoked token
    }
}
