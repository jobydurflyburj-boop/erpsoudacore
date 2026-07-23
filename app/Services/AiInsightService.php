<?php

namespace App\Services;

use App\Models\AiActivityLog;
use App\Models\AiPromptTemplate;
use App\Models\AiSuggestion;
use App\Models\Role;
use App\Models\User;
use App\Services\Ai\LlmProviderInterface;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * The real engine behind AI Dashboard/Sales/Inventory/Financial/CRM
 * Insights and AI Report Summaries: pulls real data from the same
 * already-audited services every other module's dashboards use
 * (never re-derives its own numbers), asks the tenant's configured
 * LLM (if any) to narrate it using the tenant's active prompt
 * template, and falls back to a real deterministic summary — the same
 * graceful-degradation pattern AiAssistantService established —
 * when no provider is configured or the call fails. Every call is
 * logged to ai_activity_logs. Certain insight types also detect real
 * conditions worth surfacing as Automation Suggestions, and notify
 * the tenant's Owner/Admin users for real (AI Notifications) the
 * first time a condition is detected — never a repeat notification
 * for a condition already open.
 */
class AiInsightService
{
    public function __construct(
        private readonly LlmProviderInterface $llm,
        private readonly AiPromptService $prompts,
        private readonly AiSettingsService $settings,
        private readonly AnalyticsDashboardService $analytics,
        private readonly ReportService $reports,
        private readonly CrmReportService $crmReports,
        private readonly NotificationService $notifications,
    ) {}

    public function dashboardInsight(User $user): array
    {
        $data = $this->analytics->executiveSummary();

        return $this->generate($user, AiPromptTemplate::KEY_DASHBOARD_INSIGHTS, 'dashboard_insight', $data, fn () =>
            "Cash position is SAR ".number_format($data['cash_position'], 2).". You have {$data['active_employees']} active employee(s), ".
            "{$data['open_leads']} open lead(s), and {$data['low_stock_products']} product(s) at or below reorder point."
        );
    }

    public function salesInsight(User $user): array
    {
        $data = $this->reports->salesSummary();

        $suggestion = null;
        $overdueAmount = (float) ($data['total_outstanding'] ?? 0);
        if ($overdueAmount > 0) {
            $suggestion = $this->raiseSuggestion(
                $user->tenant_id, 'overdue_followup', 'Outstanding receivables need follow-up',
                "SAR ".number_format($overdueAmount, 2)." in invoices are outstanding. Consider following up with customers."
            );
        }

        $reply = $this->generate($user, AiPromptTemplate::KEY_SALES_INSIGHTS, 'sales_insight', $data, fn () =>
            "Total invoiced: SAR ".number_format((float) ($data['total_invoiced'] ?? 0), 2).". Outstanding: SAR ".
            number_format($overdueAmount, 2).". Invoices this month: ".($data['invoices_this_month'] ?? 0)."."
        );

        return array_merge($reply, ['suggestion_raised' => $suggestion !== null]);
    }

    public function inventoryInsight(User $user): array
    {
        $products = \App\Models\Product::where('is_active', true)->get();
        $lowStock = $products->filter(fn ($p) => $p->totalStock() <= (float) $p->reorder_point && $p->reorder_point > 0);
        $data = ['total_products' => $products->count(), 'low_stock_count' => $lowStock->count(), 'low_stock_skus' => $lowStock->pluck('sku')->values()->all()];

        $suggestion = null;
        if ($lowStock->count() > 0) {
            $suggestion = $this->raiseSuggestion(
                $user->tenant_id, 'inventory_reorder', 'Products need reordering',
                "{$lowStock->count()} product(s) are at or below their reorder point: ".$lowStock->pluck('sku')->implode(', ')
            );
        }

        $reply = $this->generate($user, AiPromptTemplate::KEY_INVENTORY_INSIGHTS, 'inventory_insight', $data, fn () =>
            "You have {$data['total_products']} active product(s), {$data['low_stock_count']} of which are at or below reorder point."
        );

        return array_merge($reply, ['suggestion_raised' => $suggestion !== null]);
    }

    public function financialInsight(User $user): array
    {
        $data = $this->analytics->executiveSummary();

        $suggestion = null;
        if ($data['cash_position'] < 0) {
            $suggestion = $this->raiseSuggestion(
                $user->tenant_id, 'cash_flow_risk', 'Cash position is negative',
                "Cash position is SAR ".number_format($data['cash_position'], 2).". Review upcoming payables and receivables."
            );
        }

        return array_merge(
            $this->generate($user, AiPromptTemplate::KEY_FINANCIAL_INSIGHTS, 'financial_insight', $data, fn () =>
                "Cash: SAR ".number_format($data['cash_position'], 2).". Receivable: SAR ".number_format($data['accounts_receivable'], 2).
                ". Payable: SAR ".number_format($data['accounts_payable'], 2)."."
            ),
            ['suggestion_raised' => $suggestion !== null]
        );
    }

    public function crmInsight(User $user): array
    {
        $data = $this->crmReports->conversionFunnel();

        return $this->generate($user, AiPromptTemplate::KEY_CRM_INSIGHTS, 'crm_insight', $data, fn () =>
            "You have {$data['total_leads']} lead(s), {$data['won_leads']} won, {$data['converted_to_customer']} converted to customers ".
            "({$data['lead_to_customer_rate']}% rate). {$data['total_opportunities']} opportunit(y/ies) with a {$data['opportunity_win_rate']}% win rate."
        );
    }

    /** Generic AI Report Summary: narrates any already-computed report payload (from a built-in or custom report) using the dashboard prompt as a sensible default tone. */
    public function reportSummary(User $user, string $reportLabel, array $data): array
    {
        return $this->generate(
            $user, AiPromptTemplate::KEY_DASHBOARD_INSIGHTS, 'report_summary', $data,
            fn () => "Report '{$reportLabel}' contains ".count($data)." data point(s). Configure an AI provider for a narrative summary.",
            contextLabel: $reportLabel
        );
    }

    private function generate(User $user, string $promptKey, string $feature, array $data, \Closure $fallback, ?string $contextLabel = null): array
    {
        $setting = $this->settings->get($user->tenant_id);
        if (! $setting->is_enabled || ! $setting->insights_enabled) {
            return ['summary' => 'AI insights are turned off for this workspace. Enable them in AI Settings to see a narrative summary here.', 'provider' => null, 'model' => null];
        }

        $summary = $fallback();
        $provider = null;
        $model = null;

        if ($this->llm->isConfigured()) {
            try {
                $systemPrompt = $this->prompts->resolve($user->tenant_id, $promptKey);
                $context = ($contextLabel ? "Report: {$contextLabel}\n" : '')."Real data:\n".json_encode($data, JSON_PRETTY_PRINT);
                $summary = $this->llm->complete($systemPrompt, [], $context);
                $provider = $this->llm->name();
                $model = $this->llm->model();
            } catch (\Throwable $e) {
                Log::warning('AI insight generation failed, using deterministic summary', ['feature' => $feature, 'error' => $e->getMessage()]);
            }
        }

        $this->log($user, $feature, $provider, $model, $summary);

        return ['summary' => $summary, 'provider' => $provider, 'model' => $model];
    }

    /** Idempotent per condition: never opens a second suggestion for the same tenant+category while one is already open. */
    private function raiseSuggestion(string $tenantId, string $category, string $title, string $description): ?AiSuggestion
    {
        $setting = \App\Models\AiSetting::firstOrCreate(['tenant_id' => $tenantId]);
        if (! $setting->is_enabled || ! $setting->automation_suggestions_enabled) {
            return null;
        }

        $existing = AiSuggestion::where('tenant_id', $tenantId)->where('category', $category)->where('status', AiSuggestion::STATUS_OPEN)->first();
        if ($existing) {
            return $existing; // already surfaced — don't spam a duplicate suggestion or a duplicate notification
        }

        $suggestion = AiSuggestion::create([
            'tenant_id' => $tenantId, 'category' => $category, 'title' => $title,
            'description' => $description, 'status' => AiSuggestion::STATUS_OPEN,
        ]);

        if ($setting->notifications_enabled) {
            $this->notifyAdmins($tenantId, $title, $description);
        }

        return $suggestion;
    }

    private function notifyAdmins(string $tenantId, string $title, string $body): void
    {
        $admins = User::where('tenant_id', $tenantId)
            ->whereHas('role', fn ($q) => $q->whereIn('code', [Role::COMPANY_OWNER, Role::ADMIN]))
            ->get();

        foreach ($admins as $admin) {
            $this->notifications->send($admin, 'ai_assistant', $title, $body);
        }
    }

    private function log(User $user, string $feature, ?string $provider, ?string $model, string $summary): void
    {
        AiActivityLog::create([
            'tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'feature' => $feature,
            'provider' => $provider, 'model' => $model,
            'summary' => \Illuminate\Support\Str::limit($summary, 500),
            'created_at' => now(),
        ]);
    }
}
