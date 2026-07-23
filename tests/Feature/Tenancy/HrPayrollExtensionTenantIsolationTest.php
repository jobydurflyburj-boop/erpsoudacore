<?php

namespace Tests\Feature\Tenancy;

use App\Models\Employee;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HrPayrollExtensionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'HR Ext Isolation Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_employees_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('hr-ext-iso-a');
        [$tenantB] = $this->registerTenant('hr-ext-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        Employee::create([
            'tenant_id' => $tenantB->id, 'employee_number' => 'EMP-777777', 'full_name' => 'Hidden Employee',
            'hire_date' => now()->toDateString(), 'basic_salary' => 9999,
        ]);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('employees')->where('employee_number', 'EMP-777777')->get();
        $this->assertCount(0, $rows);

        $context->reset();
    }

    public function test_employee_numbers_are_independent_per_tenant(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('hr-emp-seq-a');
        [$tenantB, $ownerB] = $this->registerTenant('hr-emp-seq-b');

        $context = app(TenantContext::class);

        $context->set($tenantA);
        $context->apply();
        $employeeA = app(\App\Services\EmployeeService::class)->create($ownerA, [
            'full_name' => 'Employee A', 'hire_date' => now()->toDateString(), 'basic_salary' => 5000,
        ]);

        $context->set($tenantB);
        $context->apply();
        $employeeB = app(\App\Services\EmployeeService::class)->create($ownerB, [
            'full_name' => 'Employee B', 'hire_date' => now()->toDateString(), 'basic_salary' => 5000,
        ]);

        $context->reset();

        $this->assertEquals('EMP-000001', $employeeA->employee_number);
        $this->assertEquals('EMP-000001', $employeeB->employee_number);
    }
}
