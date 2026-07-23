<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Every figure here is a real query against the leads/customers/
 * opportunities tables — no placeholder numbers. Scoped to the caller's
 * own book (leads assigned to them / customers they manage /
 * opportunities they own) when their role is in
 * Lead::OWN_RECORDS_ONLY_ROLES (Sales), company-wide otherwise — the
 * same visibility rule Lead/Customer/OpportunityPolicy enforce on
 * individual records, applied here to aggregates instead.
 */
class CrmDashboardService
{
    public function summary(User $user): array
    {
        $scoped = $this->scopedQuery($user);

        return [
            'totals' => $this->totals($user),
            'pipeline' => $this->pipelineByStatus($user),
            'by_source' => $this->bySource($user),
            'by_priority' => (clone $scoped)->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')->pluck('count', 'priority'),
            'recent_leads' => (clone $scoped)->with(['status', 'source', 'assignee'])
                ->latest('created_at')->limit(10)->get(),
            'customers' => $this->customerTotals($user),
            'opportunities' => $this->opportunityTotals($user),
            'opportunity_pipeline' => $this->opportunityPipelineByStage($user),
        ];
    }

    private function opportunityTotals(User $user): array
    {
        $query = Opportunity::query();

        if (in_array($user->role?->code, Opportunity::OWN_RECORDS_ONLY_ROLES, true)) {
            $query->where('assigned_to_user_id', $user->id);
        }

        $wonStageIds = OpportunityStage::where('is_won', true)->pluck('id');
        $lostStageIds = OpportunityStage::where('is_lost', true)->pluck('id');

        $wonCount = (clone $query)->whereIn('stage_id', $wonStageIds)->count();
        $lostCount = (clone $query)->whereIn('stage_id', $lostStageIds)->count();
        $closedCount = $wonCount + $lostCount;

        return [
            'total_open' => (clone $query)->whereNotIn('stage_id', $wonStageIds->merge($lostStageIds))->count(),
            'won_this_month' => (clone $query)->whereIn('stage_id', $wonStageIds)
                ->whereBetween('closed_at', [now()->startOfMonth(), now()])->count(),
            'lost_this_month' => (clone $query)->whereIn('stage_id', $lostStageIds)
                ->whereBetween('closed_at', [now()->startOfMonth(), now()])->count(),
            'win_rate' => $closedCount > 0 ? round(($wonCount / $closedCount) * 100, 1) : 0.0,
            'open_pipeline_value' => (float) (clone $query)
                ->whereNotIn('stage_id', $wonStageIds->merge($lostStageIds))
                ->sum(DB::raw('amount * probability / 100')),
        ];
    }

    private function opportunityPipelineByStage(User $user): \Illuminate\Support\Collection
    {
        $query = Opportunity::query();

        if (in_array($user->role?->code, Opportunity::OWN_RECORDS_ONLY_ROLES, true)) {
            $query->where('assigned_to_user_id', $user->id);
        }

        $counts = (clone $query)->select('stage_id', DB::raw('count(*) as count'), DB::raw('coalesce(sum(amount), 0) as total_amount'))
            ->groupBy('stage_id')
            ->get()
            ->keyBy('stage_id');

        return OpportunityStage::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (OpportunityStage $stage) => [
                'stage_id' => $stage->id,
                'name_en' => $stage->name_en,
                'name_ar' => $stage->name_ar,
                'color' => $stage->color,
                'count' => (int) ($counts[$stage->id]->count ?? 0),
                'total_amount' => (float) ($counts[$stage->id]->total_amount ?? 0),
            ]);
    }

    private function customerTotals(User $user): array
    {
        $query = Customer::query();

        if (in_array($user->role?->code, Customer::OWN_RECORDS_ONLY_ROLES, true)) {
            $query->where('account_manager_user_id', $user->id);
        }

        return [
            'total_customers' => (clone $query)->count(),
            'new_customers_this_month' => (clone $query)->whereBetween('created_at', [now()->startOfMonth(), now()])->count(),
            'converted_from_leads_this_month' => (clone $query)
                ->whereNotNull('source_lead_id')
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->count(),
        ];
    }

    private function totals(User $user): array
    {
        $scoped = $this->scopedQuery($user);

        $wonStatusIds = LeadStatus::where('is_won', true)->pluck('id');
        $lostStatusIds = LeadStatus::where('is_lost', true)->pluck('id');

        $wonCount = (clone $scoped)->whereIn('lead_status_id', $wonStatusIds)->count();
        $lostCount = (clone $scoped)->whereIn('lead_status_id', $lostStatusIds)->count();
        $closedCount = $wonCount + $lostCount;

        return [
            'total_leads' => (clone $scoped)->count(),
            'leads_this_month' => (clone $scoped)->whereBetween('created_at', [now()->startOfMonth(), now()])->count(),
            'won_this_month' => (clone $scoped)->whereIn('lead_status_id', $wonStatusIds)
                ->whereBetween('updated_at', [now()->startOfMonth(), now()])->count(),
            'lost_this_month' => (clone $scoped)->whereIn('lead_status_id', $lostStatusIds)
                ->whereBetween('updated_at', [now()->startOfMonth(), now()])->count(),
            'conversion_rate' => $closedCount > 0 ? round(($wonCount / $closedCount) * 100, 1) : 0.0,
            'pipeline_value' => (float) (clone $scoped)->whereNotIn('lead_status_id', $lostStatusIds)
                ->sum(DB::raw('expected_revenue * probability / 100')),
            'unassigned_leads' => (clone $scoped)->whereNull('assigned_to_user_id')->count(),
        ];
    }

    private function pipelineByStatus(User $user): \Illuminate\Support\Collection
    {
        $scoped = $this->scopedQuery($user);

        $counts = (clone $scoped)->select('lead_status_id', DB::raw('count(*) as count'))
            ->groupBy('lead_status_id')
            ->pluck('count', 'lead_status_id');

        return LeadStatus::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LeadStatus $status) => [
                'status_id' => $status->id,
                'name_en' => $status->name_en,
                'name_ar' => $status->name_ar,
                'color' => $status->color,
                'count' => (int) ($counts[$status->id] ?? 0),
            ]);
    }

    private function bySource(User $user): \Illuminate\Support\Collection
    {
        $scoped = $this->scopedQuery($user);

        return (clone $scoped)
            ->join('lead_sources', 'lead_sources.id', '=', 'leads.lead_source_id')
            ->select('lead_sources.id', 'lead_sources.name_en', DB::raw('count(leads.id) as count'))
            ->groupBy('lead_sources.id', 'lead_sources.name_en')
            ->orderByDesc('count')
            ->get();
    }

    private function scopedQuery(User $user)
    {
        $query = Lead::query();

        if (in_array($user->role?->code, Lead::OWN_RECORDS_ONLY_ROLES, true)) {
            $query->where('assigned_to_user_id', $user->id);
        }

        return $query;
    }
}
