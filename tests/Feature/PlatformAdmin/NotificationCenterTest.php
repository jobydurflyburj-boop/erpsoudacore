<?php

namespace Tests\Feature\PlatformAdmin;

use App\Models\Company;
use App\Models\Notification as NotificationModel;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function userWithTenant(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $company = Company::factory()->for($tenant)->create();
        $role = Role::factory()->for($tenant)->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        return [$tenant, $user];
    }

    public function test_sending_a_notification_always_creates_an_in_app_row(): void
    {
        [$tenant, $user] = $this->userWithTenant();

        app(NotificationService::class)->send($user, 'task.assigned', 'You have a new task', 'Body text');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id, 'category' => 'task.assigned', 'title' => 'You have a new task',
        ]);
    }

    public function test_email_is_sent_by_default_for_a_category_with_no_explicit_preference(): void
    {
        Mail::fake();

        [$tenant, $user] = $this->userWithTenant();

        app(NotificationService::class)->send($user, 'task.assigned', 'Reminder', 'Body');

        Mail::assertQueued(\App\Mail\NotificationMail::class);
    }

    public function test_a_user_can_list_and_mark_their_own_notifications_read(): void
    {
        [$tenant, $user] = $this->userWithTenant();

        $notification = NotificationModel::create([
            'tenant_id' => $tenant->id, 'user_id' => $user->id,
            'category' => 'task.assigned', 'title' => 'Test',
        ]);

        Sanctum::actingAs($user);

        $list = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/notifications');
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));

        $unread = $this->withHeader('X-Tenant-ID', $tenant->id)->getJson('/api/v1/notifications/unread-count');
        $unread->assertJsonPath('data.unread_count', 1);

        $markRead = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/v1/notifications/{$notification->id}/read");
        $markRead->assertOk()->assertJsonPath('data.is_read', true);
    }

    public function test_assigning_a_task_to_someone_else_notifies_them(): void
    {
        [$tenant, $creator] = $this->userWithTenant();

        $company = Company::where('tenant_id', $tenant->id)->firstOrFail();
        $role = Role::where('tenant_id', $tenant->id)->firstOrFail();
        $assignee = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        app(\App\Services\TaskService::class)->create($creator, [
            'title' => 'Prepare the quarterly report',
            'assigned_to_user_id' => $assignee->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $assignee->id,
            'category' => 'task.assigned',
        ]);
    }

    public function test_a_user_who_assigns_a_task_to_themself_is_not_notified(): void
    {
        [$tenant, $creator] = $this->userWithTenant();

        app(\App\Services\TaskService::class)->create($creator, ['title' => 'My own reminder']);

        $this->assertDatabaseMissing('notifications', ['user_id' => $creator->id, 'category' => 'task.assigned']);
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        [$tenant, $user] = $this->userWithTenant();

        $company = Company::where('tenant_id', $tenant->id)->firstOrFail();
        $role = Role::where('tenant_id', $tenant->id)->firstOrFail();
        $otherUser = User::factory()->create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'role_id' => $role->id,
            'password' => Hash::make('irrelevant'),
        ]);

        $notification = NotificationModel::create([
            'tenant_id' => $tenant->id, 'user_id' => $otherUser->id,
            'category' => 'task.assigned', 'title' => 'Not yours',
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(403);
    }
}
