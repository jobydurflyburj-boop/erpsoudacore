<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\PayrollRun;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SupplierBill;
use App\Models\User;
use App\Services\Ai\LlmProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AI Assistant — real from two angles now, not just one. The
 * MVP-demo-era grounding layer (keyword-matched intents answered from
 * live Eloquent queries — never fabricated numbers) is unchanged in
 * spirit and now covers Purchase, HR & Payroll, and Accounting/Cash
 * alongside the original Leads/Customers/Opportunities/Sales/
 * Inventory. New this sprint: when a real `LlmProviderInterface` is
 * configured (see config/ai.php), the matched grounding data — if
 * any — is handed to the LLM as real context it must use rather than
 * invent, and the LLM produces free-form, multi-turn-aware language
 * around it. No LLM configured, or the LLM call fails for any reason
 * (network, auth, rate limit): falls back to the deterministic
 * grounded reply exactly as before — a real degradation path, not a
 * user-facing error. Every code path here answers questions; none of
 * them ever mutate business data — a deliberate scope boundary, not
 * an oversight (see the sprint doc).
 */
class AiAssistantService
{
    public function __construct(private readonly LlmProviderInterface $llm) {}

    public function ask(User $user, string $message, ?AiConversation $conversation = null): array
    {
        $conversation ??= AiConversation::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'title' => \Illuminate\Support\Str::limit($message, 60),
        ]);

        AiMessage::create([
            'tenant_id' => $user->tenant_id,
            'conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_USER,
            'content' => $message,
            'created_at' => now(),
        ]);

        $grounding = $this->matchGrounding($message);
        [$replyText, $provider, $model] = $this->generateReply($conversation, $message, $grounding);

        $assistantMessage = AiMessage::create([
            'tenant_id' => $user->tenant_id,
            'conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_ASSISTANT,
            'content' => $replyText,
            'provider' => $provider,
            'model' => $model,
            'created_at' => now(),
        ]);

        return ['conversation' => $conversation, 'reply' => $assistantMessage];
    }

    /** @return array{0: string, 1: ?string, 2: ?string} [reply text, provider name or null, model or null] */
    private function generateReply(AiConversation $conversation, string $message, ?string $grounding): array
    {
        if ($this->llm->isConfigured()) {
            try {
                $history = $conversation->messages()
                    ->latest('created_at')->limit(config('ai.history_window'))
                    ->get()->reverse()
                    ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
                    ->values()->all();

                $systemPrompt = $this->systemPrompt($grounding);
                $text = $this->llm->complete($systemPrompt, $history, $message);

                return [$text, $this->llm->name(), $this->llm->model()];
            } catch (\Throwable $e) {
                Log::warning('AI provider call failed, falling back to grounded reply', ['error' => $e->getMessage()]);
            }
        }

        return [$grounding ?? $this->helpMessage(), null, null];
    }

    private function systemPrompt(?string $grounding): string
    {
        $base = 'You are the AI Assistant inside SoudaCore ERP, a Saudi Arabia-focused business management system. '
            .'Answer the user\'s question helpfully and concisely. Never invent figures.';

        if ($grounding) {
            return $base."\n\nReal, current data relevant to this question:\n{$grounding}\n\n".
                'Use these exact figures in your answer where relevant. Do not contradict or alter them.';
        }

        return $base.' If the question needs specific business data you were not given, say what you can help with instead of guessing.';
    }

    private function helpMessage(): string
    {
        return "I can answer questions about leads, customers, opportunities, sales/invoices, stock/inventory, purchases, ".
            "payroll, and cash/accounts right now — try asking \"how many open leads do we have?\" or \"what's our cash position?\".";
    }

    /** Real keyword-matched intent routing to live Eloquent queries — returns null (not a fabricated answer) when nothing matches. */
    private function matchGrounding(string $message): ?string
    {
        $text = mb_strtolower($message);

        return match (true) {
            str_contains($text, 'lead') => $this->leadsAnswer(),
            str_contains($text, 'customer') => $this->customersAnswer(),
            str_contains($text, 'opportunit') || str_contains($text, 'pipeline') || str_contains($text, 'deal') => $this->opportunitiesAnswer(),
            str_contains($text, 'sale') || str_contains($text, 'revenue') || str_contains($text, 'invoice') => $this->salesAnswer(),
            str_contains($text, 'stock') || str_contains($text, 'inventory') => $this->inventoryAnswer(),
            str_contains($text, 'purchase') || str_contains($text, 'supplier') || str_contains($text, 'bill') => $this->purchaseAnswer(),
            str_contains($text, 'payroll') || str_contains($text, 'employee') || str_contains($text, 'salary') || str_contains($text, 'headcount') => $this->payrollAnswer(),
            str_contains($text, 'cash') || str_contains($text, 'account') || str_contains($text, 'payable') || str_contains($text, 'receivable') => $this->accountingAnswer(),
            default => null,
        };
    }

    private function leadsAnswer(): string
    {
        $total = Lead::whereNull('converted_to_customer_id')->count();
        $unassigned = Lead::whereNull('assigned_to_user_id')->whereNull('converted_to_customer_id')->count();

        return "You currently have {$total} open lead(s), {$unassigned} of which are unassigned.";
    }

    private function customersAnswer(): string
    {
        $total = Customer::where('status', 'active')->count();
        $newThisMonth = Customer::whereBetween('created_at', [now()->startOfMonth(), now()])->count();

        return "You have {$total} active customer(s), with {$newThisMonth} added this month.";
    }

    private function opportunitiesAnswer(): string
    {
        $open = Opportunity::whereHas('stage', fn ($q) => $q->where('is_won', false)->where('is_lost', false))->count();
        $value = (float) Opportunity::whereHas('stage', fn ($q) => $q->where('is_won', false)->where('is_lost', false))
            ->sum(DB::raw('amount * probability / 100'));

        return "You have {$open} open opportunit".($open === 1 ? 'y' : 'ies')." with a weighted pipeline value of SAR ".number_format($value, 2).".";
    }

    private function salesAnswer(): string
    {
        $thisMonth = (float) SalesInvoice::whereBetween('document_date', [now()->startOfMonth(), now()])->sum('total');
        $outstanding = (float) SalesInvoice::whereIn('status', ['issued', 'partial', 'overdue'])->sum(DB::raw('total - paid_amount'));

        return "Total invoiced this month is SAR ".number_format($thisMonth, 2).". Outstanding receivables are SAR ".number_format($outstanding, 2).".";
    }

    private function inventoryAnswer(): string
    {
        $products = Product::where('is_active', true)->get();
        $lowStock = $products->filter(fn (Product $p) => $p->totalStock() <= (float) $p->reorder_point && $p->reorder_point > 0)->count();

        return "You have {$products->count()} active product(s) in the catalog, {$lowStock} of which are at or below their reorder point.";
    }

    private function purchaseAnswer(): string
    {
        $openOrders = PurchaseOrder::where('status', PurchaseOrder::STATUS_SENT)->count();
        $outstanding = (float) SupplierBill::whereIn('status', ['approved', 'partial', 'overdue'])
            ->sum(DB::raw('total - paid_amount - credited_amount'));

        return "You have {$openOrders} open purchase order(s) sent to suppliers. Outstanding payables are SAR ".number_format($outstanding, 2).".";
    }

    private function payrollAnswer(): string
    {
        $active = Employee::where('employment_status', Employee::STATUS_ACTIVE)->count();
        $latestRun = PayrollRun::orderByDesc('period_year')->orderByDesc('period_month')->first();

        $runText = $latestRun
            ? "The latest payroll run ({$latestRun->run_number}) is {$latestRun->status} with a total net pay of SAR ".number_format((float) $latestRun->total_net, 2).'.'
            : 'No payroll run has been processed yet.';

        return "You have {$active} active employee(s). {$runText}";
    }

    private function accountingAnswer(): string
    {
        $cash = $this->accountBalance('1000');
        $receivable = $this->accountBalance('1100');
        $payable = $this->accountBalance('2000', credit: true);

        return "Cash position is SAR ".number_format($cash, 2).". Accounts receivable: SAR ".number_format($receivable, 2).
            ". Accounts payable: SAR ".number_format($payable, 2).".";
    }

    private function accountBalance(string $code, bool $credit = false): float
    {
        $account = \App\Models\ChartOfAccount::where('code', $code)->first();
        if (! $account) {
            return 0.0;
        }

        $sums = \App\Models\JournalEntryLine::where('account_id', $account->id)
            ->selectRaw('coalesce(sum(debit),0) as total_debit, coalesce(sum(credit),0) as total_credit')->first();

        return $credit
            ? round((float) $sums->total_credit - (float) $sums->total_debit, 2)
            : round((float) $sums->total_debit - (float) $sums->total_credit, 2);
    }
}
