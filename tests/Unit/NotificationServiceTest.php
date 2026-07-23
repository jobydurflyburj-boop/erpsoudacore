<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\NotificationPreference;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create();

        return User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'), 'phone' => '0501234567',
        ]);
    }

    public function test_sms_is_not_sent_without_an_explicit_opt_in(): void
    {
        Log::spy();

        $user = $this->makeUser();

        app(NotificationService::class)->send($user, 'task.assigned', 'Title');

        Log::shouldNotHaveReceived('info', fn ($message) => str_starts_with($message, '[SMS]'));
    }

    public function test_sms_is_sent_once_the_user_opts_in_for_that_category(): void
    {
        Log::spy();

        $user = $this->makeUser();

        NotificationPreference::create([
            'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
            'category' => 'task.assigned', 'channel' => 'sms', 'enabled' => true,
        ]);

        app(NotificationService::class)->send($user, 'task.assigned', 'Title');

        Log::shouldHaveReceived('info')->withArgs(fn ($message) => str_starts_with($message, '[SMS]'))->once();
    }

    public function test_email_can_be_explicitly_disabled_per_category(): void
    {
        Mail::fake();

        $user = $this->makeUser();

        NotificationPreference::create([
            'tenant_id' => $user->tenant_id, 'user_id' => $user->id,
            'category' => 'task.assigned', 'channel' => 'email', 'enabled' => false,
        ]);

        app(NotificationService::class)->send($user, 'task.assigned', 'Title');

        Mail::assertNothingQueued();
    }
}
