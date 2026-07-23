<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class PasswordPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_recently_used_password_is_rejected(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        $service = app(PasswordPolicyService::class);
        $service->recordHistory($user, Hash::make('MyOldPassphrase!1'));

        $this->expectException(InvalidArgumentException::class);
        $service->assertNotReused($user, 'MyOldPassphrase!1');
    }

    public function test_a_new_unused_password_passes(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        $service = app(PasswordPolicyService::class);
        $service->recordHistory($user, Hash::make('MyOldPassphrase!1'));

        $service->assertNotReused($user, 'ACompletelyDifferentPassphrase!2');
        $this->assertTrue(true); // no exception thrown = pass
    }
}
