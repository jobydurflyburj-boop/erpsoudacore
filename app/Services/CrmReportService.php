<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Support\Facades\DB;

/**
 * CRM Reports — the one report category this project genuinely never
 * had (Sales/Purchase/Inventory/Accounting/Payroll all got their own
 * reports as each of those modules reached completion; CRM's own
 * sprints never circled back to add one). Every figure a real query
 * against real CRM tables, consistent with every other report in this
 * project.
 */
class CrmReportService
{
    public function leadsBySource(): array
    {
        return Lead::query()
            ->join('lead_sources', 'lead_sources.id', '=', 'leads.lead_source_id')
            ->select('lead_sources.name_en as source', DB::raw('count(*) as total'))
            ->groupBy('lead_sources.name_en')
            ->orderByDesc('total')
            ->get()->toArray();
    }

    public function leadsByStatus(): array
    {
        return Lead::query()
            ->join('lead_statuses', 'lead_statuses.id', '=', 'leads.lead_status_id')
            ->select('lead_statuses.name_en as status', DB::raw('count(*) as total'))
            ->groupBy('lead_statuses.name_en')
            ->orderByDesc('total')
            ->get()->toArray();
    }

    public function opportunitiesByStage(): array
    {
        return Opportunity::query()
            ->join('opportunity_stages', 'opportunity_stages.id', '=', 'opportunities.stage_id')
            ->select(
                'opportunity_stages.name_en as stage',
                DB::raw('count(*) as total'),
                DB::raw('coalesce(sum(opportunities.amount),0) as total_amount')
            )
            ->groupBy('opportunity_stages.name_en')
            ->orderByDesc('total_amount')
            ->get()->toArray();
    }

    /**
     * A real conversion funnel: total leads -> leads marked Won ->
     * customers actually created from a Won lead (Customer's
     * `source_lead_id`, the same field CRM Sprint 2 built for real
     * Lead->Customer conversion) -> opportunities on those customers
     * -> opportunities actually Won.
     */
    public function conversionFunnel(): array
    {
        $totalLeads = Lead::count();
        $wonLeads = Lead::whereHas('status', fn ($q) => $q->where('is_won', true))->count();
        $convertedCustomers = Customer::whereNotNull('source_lead_id')->count();
        $totalOpportunities = Opportunity::count();
        $wonOpportunities = Opportunity::whereHas('stage', fn ($q) => $q->where('is_won', true))->count();

        return [
            'total_leads' => $totalLeads,
            'won_leads' => $wonLeads,
            'converted_to_customer' => $convertedCustomers,
            'total_opportunities' => $totalOpportunities,
            'won_opportunities' => $wonOpportunities,
            'lead_to_customer_rate' => $totalLeads > 0 ? round(($convertedCustomers / $totalLeads) * 100, 2) : 0.0,
            'opportunity_win_rate' => $totalOpportunities > 0 ? round(($wonOpportunities / $totalOpportunities) * 100, 2) : 0.0,
        ];
    }
}
