<?php

namespace Tests\Feature\Hr;

use App\Models\Employee;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HrReportsDashboardAndEssTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'HR Reports Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_hr_dashboard_and_payroll_summary_report_return_real_data_shapes(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('hr-dashboard-reports');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->postJson('/api/v1/hr/employees', [
            'full_name' => 'Dashboard Test Employee', 'hire_date' => now()->toDateString(), 'basic_salary' => 6000,
        ])->assertCreated();

        $dashboard = $h()->getJson('/api/v1/hr/dashboard');
        $dashboard->assertOk()->assertJsonStructure(['data' => ['employee_counts', 'attendance_today', 'pending_leave_requests']]);
        $this->assertEquals(1, $dashboard->json('data.employee_counts.active'));

        $report = $h()->getJson('/api/v1/reports/payroll-summary');
        $report->assertOk()->assertJsonStructure(['data' => ['runs', 'by_department']]);
    }

    /**
     * Employee Self-Service resolves strictly from the authenticated
     * user's own Employee record — never a client-supplied id. A user
     * with no linked Employee record gets a clear error, not silent
     * success or someone else's data.
     */
    public function test_employee_self_service_is_scoped_to_the_callers_own_employee_record(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('hr-ess-scoping');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        // The owner has no linked Employee record — a clear error, not empty success.
        $h()->getJson('/api/v1/ess/profile')->assertStatus(422);

        // Link a real Employee to the owner's own user account.
        $employee = Employee::create([
            'tenant_id' => $tenant->id, 'employee_number' => 'EMP-000001', 'user_id' => $owner->id,
            'full_name' => $owner->full_name, 'hire_date' => now()->toDateString(), 'basic_salary' => 7000,
        ]);

        $profile = $h()->getJson('/api/v1/ess/profile');
        $profile->assertOk();
        $this->assertEquals($employee->id, $profile->json('data.id'));

        $checkIn = $h()->postJson('/api/v1/ess/attendance/check-in');
        $checkIn->assertCreated();
        $this->assertDatabaseHas('attendances', ['tenant_id' => $tenant->id, 'employee_id' => $employee->id, 'date' => now()->toDateString()]);
    }
}
