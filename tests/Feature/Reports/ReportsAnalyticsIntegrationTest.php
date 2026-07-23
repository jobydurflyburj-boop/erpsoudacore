<?php

namespace Tests\Feature\Reports;

use App\Models\CustomReport;
use App\Models\ScheduledReport;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Reports & Analytics completion: Executive/KPI dashboards, CRM
 * Reports, Cash Flow, VAT Report, the Custom Report Builder's
 * allow-list safety, real export formats, and Scheduled Report
 * delivery.
 */
class ReportsAnalyticsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenantWithOwner(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Reports Analytics Test Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_executive_and_kpi_dashboards_return_real_data_shapes(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ra-dashboards');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $exec = $h()->getJson('/api/v1/reports/executive-summary');
        $exec->assertOk()->assertJsonStructure(['data' => [
            'cash_position', 'accounts_receivable', 'accounts_payable', 'sales_this_month',
            'purchases_this_month', 'open_purchase_orders', 'active_employees', 'open_leads',
            'open_opportunity_value', 'low_stock_products',
        ]]);

        $kpi = $h()->getJson('/api/v1/reports/kpi-summary');
        $kpi->assertOk()->assertJsonStructure(['data' => ['revenue', 'purchase_spend', 'new_leads', 'headcount']]);
        // No prior-month activity on a brand new tenant — a null change_percent, not a fabricated 0%.
        $this->assertNull($kpi->json('data.revenue.change_percent'));
    }

    public function test_crm_reports_reflect_real_leads_and_opportunities(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ra-crm-reports');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $sourceId = $h()->getJson('/api/v1/crm/lead-sources')->json('data.0.id');
        $statusId = $h()->getJson('/api/v1/crm/lead-statuses')->json('data.0.id');
        $h()->postJson('/api/v1/crm/leads', [
            'company_name' => 'Acme Co', 'first_name' => 'Ali', 'last_name' => 'Hassan',
            'lead_source_id' => $sourceId, 'lead_status_id' => $statusId,
        ])->assertCreated();

        $bySource = $h()->getJson('/api/v1/reports/leads-by-source');
        $bySource->assertOk();
        $this->assertEquals(1, collect($bySource->json('data'))->sum('total'));

        $funnel = $h()->getJson('/api/v1/reports/conversion-funnel');
        $funnel->assertOk();
        $this->assertEquals(1, $funnel->json('data.total_leads'));
    }

    public function test_custom_report_builder_rejects_invalid_source_and_column_but_accepts_and_runs_a_valid_definition(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ra-custom-reports');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        // Invalid source rejected by the Form Request itself.
        $h()->postJson('/api/v1/reports/custom-reports', [
            'name' => 'Bad Source', 'source' => 'not_a_real_table', 'columns' => ['id'],
        ])->assertStatus(422);

        // Valid source, but a column that isn't on the allow-list — rejected by CustomReportService.
        $h()->postJson('/api/v1/reports/custom-reports', [
            'name' => 'Bad Column', 'source' => 'employees', 'columns' => ['password', 'id'],
        ])->assertStatus(422);

        // A genuinely valid definition.
        $reportId = $h()->postJson('/api/v1/reports/custom-reports', [
            'name' => 'Active Employees', 'source' => 'employees', 'columns' => ['employee_number', 'full_name', 'basic_salary'],
        ])->assertCreated()->json('data.id');

        $h()->postJson('/api/v1/hr/employees', [
            'full_name' => 'Report Test Employee', 'hire_date' => now()->toDateString(), 'basic_salary' => 5000,
        ])->assertCreated();

        $run = $h()->getJson("/api/v1/reports/custom-reports/{$reportId}/run");
        $run->assertOk();
        $this->assertCount(1, $run->json('data'));
        $this->assertEquals('Report Test Employee', $run->json('data.0.full_name'));

        // Real CSV export — correct content-type, real bytes.
        $csv = $h()->get("/api/v1/reports/custom-reports/{$reportId}/run?format=csv");
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'));
        $this->assertStringContainsString('Report Test Employee', $csv->getContent());
    }

    public function test_scheduled_report_next_run_at_is_computed_per_frequency_and_process_sends_real_email(): void
    {
        Mail::fake();
        [$tenant, $owner] = $this->registerTenantWithOwner('ra-scheduled-reports');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $reportId = $h()->postJson('/api/v1/reports/custom-reports', [
            'name' => 'Employee List', 'source' => 'employees', 'columns' => ['employee_number', 'full_name'],
        ])->json('data.id');

        $scheduleId = $h()->postJson('/api/v1/reports/scheduled-reports', [
            'name' => 'Weekly Employee Export', 'custom_report_id' => $reportId, 'frequency' => 'weekly',
            'format' => 'csv', 'recipients' => ['manager@example.com'],
        ])->assertCreated()->json('data.id');

        $schedule = ScheduledReport::find($scheduleId);
        $this->assertTrue($schedule->next_run_at->greaterThan(now()->addDays(6)));
        $this->assertTrue($schedule->next_run_at->lessThan(now()->addDays(8)));

        $h()->postJson("/api/v1/reports/scheduled-reports/{$scheduleId}/run-now")->assertOk();

        Mail::assertSent(\App\Mail\ScheduledReportMail::class, fn ($mail) => $mail->hasTo('manager@example.com'));

        $schedule->refresh();
        $this->assertNotNull($schedule->last_run_at);
    }

    public function test_the_built_in_report_export_endpoint_rejects_an_unknown_key_and_exports_a_real_pdf_for_a_known_one(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ra-export');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $h()->get('/api/v1/reports/export/not_a_real_report?format=csv')->assertStatus(422);

        $pdf = $h()->get('/api/v1/reports/export/trial_balance?format=pdf');
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-1.4', $pdf->getContent());
    }

    public function test_cash_flow_and_vat_report_return_real_data_shapes(): void
    {
        [$tenant, $owner] = $this->registerTenantWithOwner('ra-cash-vat');
        Sanctum::actingAs($owner);
        $h = fn () => $this->withHeader('X-Tenant-ID', $tenant->id);

        $cashFlow = $h()->getJson('/api/v1/reports/cash-flow');
        $cashFlow->assertOk()->assertJsonStructure(['data' => ['months', 'total_cash_in', 'total_cash_out', 'net_cash_flow']]);

        $vat = $h()->getJson('/api/v1/reports/vat-report');
        $vat->assertOk()->assertJsonStructure(['data' => ['output_vat_collected', 'input_vat_paid', 'net_vat_payable']]);
        // A brand-new tenant has posted no VAT yet.
        $this->assertEquals(0, $vat->json('data.net_vat_payable'));
    }
}
