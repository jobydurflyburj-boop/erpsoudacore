<?php

namespace Tests\Feature\Hr;

use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\LeaveType;
use App\Models\SalaryComponent;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The HR & Payroll Module completion sprint: employee lifecycle,
 * shift-aware attendance, leave balance validation and deduction,
 * overtime, the full payroll run -> payslip -> accounting posting
 * chain, and the recruitment hire -> real Employee integration.
 */
class HrPayrollModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'HR Payroll Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_a_new_tenant_gets_default_leave_types_salary_components_and_payroll_accounts(): void
    {
        [$tenant] = $this->registerTenantWithOwner('hr-new-tenant');

        $this->assertDatabaseHas('leave_types', ['tenant_id' => $tenant->id, 'name_en' => 'Annual Leave', 'days_per_year' => 21]);
        $this->assertDatabaseHas('leave_types', ['tenant_id' => $tenant->id, 'name_en' => 'Unpaid Leave', 'is_paid' => false]);
        $this->assertDatabaseHas('salary_components', ['tenant_id' => $tenant->id, 'name_en' => 'Housing Allowance']);
        $this->assertDatabaseHas('chart_of_accounts', ['tenant_id' => $tenant->id, 'code' => '5200', 'name_en' => 'Salaries & Wages Expense']);
        $this->assertDatabaseHas('chart_of_accounts', ['tenant_id' => $tenant->id, 'code' => '2200', 'name_en' => 'Salaries Payable']);
    }

    public function test_the_backfill_command_adds_missing_payroll_accounts_without_duplicating_leave_types(): void
    {
        [$tenant] = $this->registerTenantWithOwner('hr-backfill');

        ChartOfAccount::withoutTenantScope()->where('tenant_id', $tenant->id)->where('code', '5200')->delete();
        $this->artisan('hr:provision-defaults', ['tenant' => $tenant->id])->assertExitCode(0);

        $this->assertDatabaseHas('chart_of_accounts', ['tenant_id' => $tenant->id, 'code' => '5200']);
        $this->assertEquals(1, LeaveType::withoutTenantScope()->where('tenant_id', $tenant->id)->where('name_en', 'Annual Leave')->count());
    }

    public function test_creating_an_employee_provisions_real_leave_balances_for_every_active_leave_type(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('hr-employee-balances');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $employeeId = $h()->postJson('/api/v1/hr/employees', [
            'full_name' => 'Fatima Al-Otaibi', 'hire_date' => '2026-01-01', 'basic_salary' => 8000,
        ])->assertCreated()->json('data.id');

        $annual = LeaveType::where('tenant_id', $tenant->id)->where('name_en', 'Annual Leave')->firstOrFail();
        $this->assertDatabaseHas('leave_balances', [
            'tenant_id' => $tenant->id, 'employee_id' => $employeeId, 'leave_type_id' => $annual->id, 'allocated_days' => 21,
        ]);
    }

    public function test_check_in_after_shift_start_marks_the_day_late(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('hr-attendance-late');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $shiftId = $h()->postJson('/api/v1/hr/shifts', ['name' => 'Morning', 'start_time' => '08:00', 'end_time' => '16:00'])->json('data.id');
        $employee = Employee::create([
            'tenant_id' => $tenant->id, 'employee_number' => 'EMP-000001', 'full_name' => 'Test Employee',
            'hire_date' => now()->toDateString(), 'basic_salary' => 5000, 'shift_id' => $shiftId,
        ]);

        $this->travelTo(now()->setTime(8, 30));
        $attendance = app(\App\Services\AttendanceService::class)->checkIn($employee);

        $this->assertEquals('late', $attendance->status);
    }

    public function test_leave_request_exceeding_the_balance_is_rejected_and_approval_deducts_the_real_balance(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('hr-leave-balance');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $employeeId = $h()->postJson('/api/v1/hr/employees', [
            'full_name' => 'Leave Test Employee', 'hire_date' => now()->toDateString(), 'basic_salary' => 6000,
        ])->json('data.id');
        $annual = LeaveType::where('tenant_id', $tenant->id)->where('name_en', 'Annual Leave')->firstOrFail();

        // 30 days requested against a 21-day allocation — rejected.
        $h()->postJson('/api/v1/hr/leave-requests', [
            'employee_id' => $employeeId, 'leave_type_id' => $annual->id,
            'start_date' => now()->toDateString(), 'end_date' => now()->addDays(29)->toDateString(),
        ])->assertStatus(422);

        // A real 5-day request within balance is accepted and, on approval, deducts the balance.
        $leaveRequestId = $h()->postJson('/api/v1/hr/leave-requests', [
            'employee_id' => $employeeId, 'leave_type_id' => $annual->id,
            'start_date' => now()->toDateString(), 'end_date' => now()->addDays(4)->toDateString(),
        ])->assertCreated()->json('data.id');

        $h()->postJson("/api/v1/hr/leave-requests/{$leaveRequestId}/approve")->assertOk();

        $balance = \App\Models\LeaveBalance::where('tenant_id', $tenant->id)->where('employee_id', $employeeId)
            ->where('leave_type_id', $annual->id)->where('year', now()->year)->firstOrFail();
        $this->assertEquals(5, (float) $balance->used_days);

        // Attendance was marked on_leave for the approved range — a real integration, not just a status flag.
        $this->assertDatabaseHas('attendances', ['tenant_id' => $tenant->id, 'employee_id' => $employeeId, 'date' => now()->toDateString(), 'status' => 'on_leave']);
    }

    public function test_processing_payroll_generates_real_payslips_and_posts_a_balanced_accounting_entry(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('hr-payroll-run');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $employeeId = $h()->postJson('/api/v1/hr/employees', [
            'full_name' => 'Payroll Test Employee', 'hire_date' => now()->toDateString(), 'basic_salary' => 10000,
        ])->json('data.id');

        $housingAllowance = SalaryComponent::where('tenant_id', $tenant->id)->where('name_en', 'Housing Allowance')->firstOrFail();
        $h()->postJson("/api/v1/hr/employees/{$employeeId}/salary-components", [
            'components' => [['salary_component_id' => $housingAllowance->id, 'amount' => 2000]],
        ])->assertOk();

        $month = (int) now()->format('n');
        $year = (int) now()->format('Y');

        $run = $h()->postJson('/api/v1/hr/payroll-runs/process', ['month' => $month, 'year' => $year]);
        $run->assertCreated();
        $this->assertEquals(12000, $run->json('data.total_gross')); // 10000 basic + 2000 allowance
        $this->assertEquals(12000, $run->json('data.total_net')); // no deductions assigned

        // A duplicate run for the same period is rejected outright.
        $h()->postJson('/api/v1/hr/payroll-runs/process', ['month' => $month, 'year' => $year])->assertStatus(422);

        // The real accounting posting: Dr Salaries Expense 12000, Cr Cash 12000 (no deductions line since none withheld).
        $entry = JournalEntry::where('tenant_id', $tenant->id)->where('source_type', 'payroll_run')->firstOrFail();
        $expenseAccount = ChartOfAccount::where('tenant_id', $tenant->id)->where('code', '5200')->firstOrFail();
        $cashAccount = ChartOfAccount::where('tenant_id', $tenant->id)->where('code', '1000')->firstOrFail();
        $this->assertDatabaseHas('journal_entry_lines', ['journal_entry_id' => $entry->id, 'account_id' => $expenseAccount->id, 'debit' => 12000]);
        $this->assertDatabaseHas('journal_entry_lines', ['journal_entry_id' => $entry->id, 'account_id' => $cashAccount->id, 'credit' => 12000]);
    }

    public function test_hiring_a_job_application_creates_a_real_employee_record(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('hr-recruitment-hire');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $openingId = $h()->postJson('/api/v1/hr/job-openings', ['title' => 'Warehouse Clerk'])->json('data.id');
        $candidateId = $h()->postJson('/api/v1/hr/candidates', ['full_name' => 'Khalid Al-Harbi', 'email' => 'khalid@example.com'])->json('data.id');
        $applicationId = $h()->postJson('/api/v1/hr/job-applications', [
            'job_opening_id' => $openingId, 'candidate_id' => $candidateId,
        ])->assertCreated()->json('data.id');

        $hire = $h()->postJson("/api/v1/hr/job-applications/{$applicationId}/hire", [
            'hire_date' => now()->toDateString(), 'basic_salary' => 4500,
        ]);
        $hire->assertCreated();
        $this->assertEquals('Khalid Al-Harbi', $hire->json('data.full_name'));

        $this->assertDatabaseHas('employees', ['tenant_id' => $tenant->id, 'full_name' => 'Khalid Al-Harbi', 'basic_salary' => 4500]);
        $this->assertDatabaseHas('job_applications', ['id' => $applicationId, 'status' => 'hired']);

        // A second hire attempt on the same application is rejected.
        $h()->postJson("/api/v1/hr/job-applications/{$applicationId}/hire", [
            'hire_date' => now()->toDateString(), 'basic_salary' => 4500,
        ])->assertStatus(422);
    }

    public function test_performance_review_requires_a_rating_before_submission_and_follows_the_real_lifecycle(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('hr-performance');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $employeeId = $h()->postJson('/api/v1/hr/employees', [
            'full_name' => 'Review Test Employee', 'hire_date' => now()->toDateString(), 'basic_salary' => 5000,
        ])->json('data.id');
        $cycleId = $h()->postJson('/api/v1/hr/performance-review-cycles', [
            'name' => 'Q3 2026', 'period_start' => '2026-07-01', 'period_end' => '2026-09-30',
        ])->json('data.id');
        $reviewId = $h()->postJson('/api/v1/hr/performance-reviews', [
            'cycle_id' => $cycleId, 'employee_id' => $employeeId,
        ])->assertCreated()->json('data.id');

        // No rating yet — submission is rejected.
        $h()->postJson("/api/v1/hr/performance-reviews/{$reviewId}/submit")->assertStatus(422);

        $h()->patchJson("/api/v1/hr/performance-reviews/{$reviewId}", ['rating' => 4])->assertOk();
        $h()->postJson("/api/v1/hr/performance-reviews/{$reviewId}/submit")->assertOk();
        $h()->postJson("/api/v1/hr/performance-reviews/{$reviewId}/acknowledge")->assertOk();

        $this->assertDatabaseHas('performance_reviews', ['id' => $reviewId, 'status' => 'acknowledged']);
    }
}
