<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Multitenancy\TenantContext;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Services\CustomerService;
use App\Services\LeadService;
use Illuminate\Database\Seeder;

/**
 * Real, minimal demo data for the tenant DemoTenantSeeder creates —
 * genuinely linked records via the same real services/repositories
 * every controller uses (never a raw fixture dump bypassing real
 * business logic — sequence numbering, defaults, and validation-
 * adjacent behavior all run for real). Deliberately modest in scope:
 * enough to demonstrate the system actually working end-to-end
 * across CRM/Inventory/Purchase, not exhaustive sample data for every
 * module — a full demo dataset across all eleven business modules
 * would be a real, separate undertaking of its own. Local/dev only,
 * same guard as DemoTenantSeeder.
 */
class DemoDataSeeder extends Seeder
{
    public function run(Tenant $tenant, User $owner): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        app(TenantContext::class)->set($tenant);
        app(TenantContext::class)->apply();

        $products = $this->seedProducts($tenant);
        $supplier = $this->seedSupplier($tenant);
        $lead = $this->seedLead($tenant, $owner);
        $this->seedCustomerFromLead($tenant, $owner, $lead);

        app(TenantContext::class)->reset();
    }

    private function seedProducts(Tenant $tenant): array
    {
        $repository = app(ProductRepositoryInterface::class);

        return [
            $repository->create([
                'tenant_id' => $tenant->id, 'sku' => 'DEMO-001', 'name_en' => 'Office Chair — Ergonomic',
                'name_ar' => 'كرسي مكتب مريح', 'cost_price' => 350.00, 'sale_price' => 549.00,
                'vat_rate' => 15.00, 'reorder_point' => 5, 'is_active' => true,
            ]),
            $repository->create([
                'tenant_id' => $tenant->id, 'sku' => 'DEMO-002', 'name_en' => 'Laptop Stand — Aluminum',
                'name_ar' => 'حامل لابتوب ألمنيوم', 'cost_price' => 60.00, 'sale_price' => 129.00,
                'vat_rate' => 15.00, 'reorder_point' => 10, 'is_active' => true,
            ]),
        ];
    }

    private function seedSupplier(Tenant $tenant): mixed
    {
        $supplierNumber = app(\App\Services\SequenceService::class)->next($tenant->id, 'supplier_number', 'SUP');

        return app(SupplierRepositoryInterface::class)->create([
            'tenant_id' => $tenant->id, 'supplier_number' => $supplierNumber,
            'name' => 'Al-Riyadh Office Supplies Co.',
            'email' => 'sales@demo-supplier.example', 'phone' => '+966500000000',
            'payment_terms_days' => 30, 'is_active' => true,
        ]);
    }

    private function seedLead(Tenant $tenant, User $owner): Lead
    {
        $source = LeadSource::where('tenant_id', $tenant->id)->first();
        $status = LeadStatus::where('tenant_id', $tenant->id)->where('is_default', true)->first()
            ?? LeadStatus::where('tenant_id', $tenant->id)->first();

        return app(LeadService::class)->create($owner, [
            'company_name' => 'Najd Trading Establishment', 'first_name' => 'Faisal', 'last_name' => 'Al-Qahtani',
            'email' => 'faisal@najd-trading.example', 'phone' => '+966511111111', 'city' => 'Riyadh', 'country' => 'SA',
            'lead_source_id' => $source?->id, 'lead_status_id' => $status?->id,
            'expected_revenue' => 25000.00, 'probability' => 60, 'priority' => 'medium',
        ]);
    }

    private function seedCustomerFromLead(Tenant $tenant, User $owner, Lead $lead): Customer
    {
        return app(CustomerService::class)->create($owner, [
            'customer_type' => 'company', 'company_name' => 'Al-Yamama Contracting',
            'first_name' => 'Sara', 'last_name' => 'Al-Otaibi', 'email' => 'sara@alyamama.example',
            'phone' => '+966522222222', 'city' => 'Buraydah', 'country' => 'SA',
            'credit_limit' => 50000.00, 'payment_terms_days' => 30,
            'source_lead_id' => $lead->id,
        ]);
    }
}
