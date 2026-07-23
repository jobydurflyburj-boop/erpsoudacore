<?php

namespace App\Services;

use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\OpportunityStage;
use App\Models\Tenant;

/**
 * Seeds sensible CRM defaults for a tenant — same role this class plays
 * as RoleProvisioningService does for RBAC: gives a new company a
 * working setup immediately, fully editable afterward via the
 * LeadSource/LeadStatus/OpportunityStage management endpoints (nothing
 * here is a permanent, hardcoded ceiling).
 *
 * Called from RegistrationService for new tenants, and exposed via the
 * `crm:provision-defaults` console command for backfilling tenants that
 * registered before this module (or a later addition to it, like
 * Opportunity stages in CRM Sprint 3) existed.
 */
class CrmProvisioningService
{
    private function defaultSources(): array
    {
        return ['Website', 'Referral', 'Phone Inquiry', 'Social Media', 'Trade Show', 'Cold Outreach'];
    }

    /** name_en => [name_ar, color, is_won, is_lost, is_default] */
    private function defaultStatuses(): array
    {
        return [
            'New' => ['جديد', '#3B82F6', false, false, true],
            'Contacted' => ['تم التواصل', '#8B5CF6', false, false, false],
            'Qualified' => ['مؤهل', '#F59E0B', false, false, false],
            'Proposal Sent' => ['تم إرسال العرض', '#EC4899', false, false, false],
            'Negotiation' => ['التفاوض', '#F97316', false, false, false],
            'Won' => ['تم الفوز', '#10B981', true, false, false],
            'Lost' => ['خسر', '#EF4444', false, true, false],
        ];
    }

    /** name_en => [name_ar, color, default_probability, is_won, is_lost, is_default] */
    private function defaultOpportunityStages(): array
    {
        return [
            'Qualification' => ['التأهيل', '#3B82F6', 10, false, false, true],
            'Needs Analysis' => ['تحليل الاحتياجات', '#8B5CF6', 25, false, false, false],
            'Proposal' => ['العرض المقدم', '#EC4899', 50, false, false, false],
            'Negotiation' => ['التفاوض', '#F97316', 75, false, false, false],
            'Closed Won' => ['مغلق - تم الفوز', '#10B981', 100, true, false, false],
            'Closed Lost' => ['مغلق - خسر', '#EF4444', 0, false, true, false],
        ];
    }

    public function provisionDefaults(Tenant $tenant): void
    {
        if (LeadSource::withoutTenantScope()->where('tenant_id', $tenant->id)->exists()) {
            $this->provisionOpportunityStagesIfMissing($tenant); // backfill path — see note below

            return; // lead sources/statuses already provisioned — safe to call repeatedly
        }

        foreach ($this->defaultSources() as $index => $name) {
            LeadSource::create([
                'tenant_id' => $tenant->id,
                'name_en' => $name,
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        $index = 0;
        foreach ($this->defaultStatuses() as $nameEn => [$nameAr, $color, $isWon, $isLost, $isDefault]) {
            LeadStatus::create([
                'tenant_id' => $tenant->id,
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'color' => $color,
                'is_won' => $isWon,
                'is_lost' => $isLost,
                'is_default' => $isDefault,
                'is_active' => true,
                'sort_order' => $index++,
            ]);
        }

        $this->provisionOpportunityStagesIfMissing($tenant);
    }

    /**
     * Split from the lead-sources existence check above: a tenant that
     * registered before CRM Sprint 3 (Opportunities) added this method
     * already has lead_sources rows, which would otherwise short-circuit
     * this whole method via the guard at the top — this second check
     * lets `crm:provision-defaults` backfill JUST the new stages for
     * such a tenant, without re-provisioning (and duplicating) sources/
     * statuses they already have.
     */
    private function provisionOpportunityStagesIfMissing(Tenant $tenant): void
    {
        if (OpportunityStage::withoutTenantScope()->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $index = 0;
        foreach ($this->defaultOpportunityStages() as $nameEn => [$nameAr, $color, $probability, $isWon, $isLost, $isDefault]) {
            OpportunityStage::create([
                'tenant_id' => $tenant->id,
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'color' => $color,
                'default_probability' => $probability,
                'is_won' => $isWon,
                'is_lost' => $isLost,
                'is_default' => $isDefault,
                'is_active' => true,
                'sort_order' => $index++,
            ]);
        }
    }
}
