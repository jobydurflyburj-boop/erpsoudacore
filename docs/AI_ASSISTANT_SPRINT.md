# AI Assistant Module — Full Completion Sprint

The AI Assistant module was completed across two sprints. The first
(summarized briefly below) delivered the provider abstraction and AI
Chat Assistant. This sprint completes the rest of the requested scope
without rebuilding any of that: a second real LLM provider (OpenAI),
AI Dashboard/Sales/Inventory/Financial/CRM Insights, AI Report
Summaries, real AI Notifications, real Automation Suggestions, an AI
Activity Log, AI Settings, and AI Prompt Management.

---

## First sprint, unchanged this sprint (see CHANGELOG [1.5.0] for full detail)

`LlmProviderInterface`, `NullLlmProvider`, `AnthropicLlmProvider`, the
chat-based `AiAssistantService` with 8 grounded intents (leads,
customers, opportunities, sales, inventory, purchase, payroll, cash/
accounts), the `/ai/ask` and `/ai/conversations` endpoints, and the
`ai_conversations`/`ai_messages` tables — all real, all tested, all
unchanged this sprint.

## What this sprint added

### A second real provider — OpenAI (completing "multiple providers through configuration")
`OpenAiLlmProvider` — a real, working integration against OpenAI's
Chat Completions API, built the same way `AnthropicLlmProvider` was:
no SDK (`composer install` still blocked), no hardcoded key (`config/
ai.php` only), loud failure on a missing key, a `RuntimeException` on
any HTTP failure rather than garbage. `api.openai.com` is **not** in
this sandbox's allowed network domains (unlike `api.anthropic.com`),
so unlike Anthropic this integration has never even been reachable to
attempt here — its request shape is still written directly from
OpenAI's public API reference and locked in with `Http::fake()` tests
that assert the exact URL, headers, and body, the same rigor applied
to the Anthropic provider regardless of reachability.

### Per-tenant provider selection — real, not just platform-wide
`ai_settings.provider_override` lets an individual tenant choose
*which configured provider* they use, without touching environment
variables. `AiServiceProvider`'s container binding resolves this at
request time (safe, since tenant-resolution middleware has already run
by the time anything asks for `LlmProviderInterface`) and — this is
the real safety property — **the override can only ever select among
providers the platform has real credentials for**. A tenant setting
`provider_override=openai` when no `OPENAI_API_KEY` exists anywhere
falls back to the platform default silently, never errors, and is
covered by a dedicated test. A tenant can never supply their own API
key through the app — that stays a deployment-time secret, never
tenant-controlled data.

### AI Dashboard / Sales / Inventory / Financial / CRM Insights
`AiInsightService` — one real engine behind all five, each pulling
data from the exact same already-audited services every other
module's own dashboard uses (`AnalyticsDashboardService`,
`ReportService`, `CrmReportService`) rather than re-deriving numbers.
The same graceful-degradation pattern `AiAssistantService` established
governs every insight: real grounding data is handed to the tenant's
configured LLM (using the tenant's own prompt template — see Prompt
Management below) if one is configured, else a real deterministic
sentence built from the same data. A provider failure degrades
invisibly to the deterministic version — never an error, never a
fabricated number either way.

### AI Report Summaries
A generic `reportSummary()` endpoint narrates any already-computed
report payload (a built-in report or a Custom Report Builder result)
using the same real LLM-or-deterministic pattern — the frontend or an
API caller can send any report's JSON and get a real summary back,
without this module needing to know that report's shape in advance.

### Real Automation Suggestions
Three insight types detect real, specific conditions worth surfacing
and raise a real `AiSuggestion` record when they're first true:
overdue receivables (Sales Insight), products at/below reorder point
(Inventory Insight), negative cash position (Financial Insight).
**Idempotent by design** — a condition already open never raises a
second suggestion or sends a second notification; it stays open until
a user dismisses it or marks it actioned. Verified explicitly: calling
the same insight twice while the condition persists creates exactly
one suggestion, not two.

### Real AI Notifications
The moment (and only the moment) a new suggestion is raised, every
Owner/Admin user for that tenant gets a real notification via the
existing `NotificationService` — the same in-app/email delivery
mechanism every other module's notifications use, not a bespoke one.
Gated by `ai_settings.notifications_enabled`.

### AI Activity Log — a real, dedicated audit trail
Distinct from `ai_messages`' own `provider`/`model` columns (which
only cover chat): every insight generation and report summary (and,
via the same logging call, any future AI feature) writes a real
`ai_activity_logs` row — tenant, user, feature, provider/model (null
means the deterministic fallback answered), and a truncated summary.
A real, queryable record of what the AI Assistant has actually done
for a tenant, not just what it said in chat.

### AI Settings — real per-tenant configuration
One row per tenant (`AiSettingsService::get()` creates it lazily on
first access — no backfill command needed for tenants that registered
before this sprint), covering: a master on/off switch, Insights on/
off, Notifications on/off, Automation Suggestions on/off, and the
provider override described above. Disabling insights returns a real,
honest explanatory message ("AI insights are turned off...") rather
than an empty or broken response — tested explicitly.

### AI Prompt Management — real, tenant-editable
`AiPromptService` resolves the active prompt for six real keys (chat,
and each of the five insight types) — a tenant's saved override if
one exists and is active, otherwise a real, sensible built-in default
(never a blank prompt). A tenant can save a new prompt, or reset back
to the default, and the resolution logic is exercised end-to-end in
tests, including rejecting an unknown prompt key outright.

## Database — verified for real, standing practice held

Two new migrations (`ai_settings`, `ai_prompt_templates`,
`ai_activity_logs`, `ai_suggestions` — four tables in one migration
plus its RLS companion), RLS enabled and forced on all four. All 82
migrations (80 prior + 2 new) run cleanly against real PostgreSQL via
`tools/db-verify/`.

## RBAC

Extended the existing `ai` permission module with `edit`/`delete`
actions (`view` already existed and remains broadly granted). Settings,
prompt template management, and suggestion actions require `ai.edit`
— Owner/Admin only, since these are configuration decisions, not
personal-productivity use like chat or viewing insights (`ai.view`,
still broadly granted to every default role).

## API

15 new endpoints under `/ai`: 5 insight endpoints, report-summary,
suggestions index/dismiss/mark-actioned, settings show/update, prompt
templates index/upsert/reset, activity-logs index — 353 total
endpoints, up from 338. All verified present; none of the prior 338
were clobbered.

## Frontend

5 new screens: AI Insights (one-click narrative for each of the five
categories, with a clear "via {provider}" or "deterministic summary"
indicator matching the honesty pattern from the chat screen),
AI Suggestions (dismiss/mark-actioned), AI Settings (real toggles + a
real provider-override dropdown), AI Prompts (edit and reset each of
the six templates inline), AI Activity Log. Verified the same way
every prior sprint's frontend work has been: the embedded JavaScript
(now 2,123 lines) extracted and run through `node --check`, and
grepped for Blade `{{` collisions (none).

## Tests

`AiAssistantExtensionIntegrationTest` (10 cases): settings
defaults/updates/validation, the disabled-insights honest message, a
real deterministic dashboard insight with real activity-log
verification, the inventory-suggestion idempotency test (calling twice
creates exactly one suggestion, with `Notification::fake()`),
suggestion dismiss/re-dismiss rejection, prompt template resolve/
override/reset, invalid prompt key rejection, and — the two most
safety-critical cases — a tenant provider override actually being used
when real credentials exist, and the same override falling back
silently (never erroring) when they don't.
`AiAssistantExtensionTenantIsolationTest` (2 cases): raw-query
invisibility of `ai_settings` across tenants, and a provider override
on one tenant never leaking to another (via `AiSettingsService`
directly, the same seam the container binding uses). `OpenAiLlmProviderTest`
(4 unit cases via `Http::fake()`, mirroring `AnthropicLlmProviderTest`'s
exact rigor): unconfigured state, the exact real request shape, a
failed response throwing rather than returning garbage, and a missing
key never attempting a network call.

## What's still explicitly out of scope

**Only two real providers implemented** (Anthropic, OpenAI) —
`LlmProviderInterface` is ready for more, none are built. **OpenAI has
never been reachable at all in this sandbox** (not in the network
policy's allowed domains) — verified only via `Http::fake()`, the same
standing limitation Anthropic has for lack of a real key. **Automation
Suggestions cover three conditions** (overdue receivables, low stock,
negative cash) — real and extensible, not exhaustive; stale leads and
payroll reminders were named as natural next categories but not built.
**No suggestion re-opens automatically** if a dismissed condition
recurs — dismissing is currently permanent for that occurrence, not
time-boxed. **Report Summaries take a generic JSON payload**, not a
report-type-aware renderer — the narrative quality depends on what's
sent, by design (keeps the endpoint generic rather than requiring this
module to know every report's shape). **No streaming responses,
function-calling, or write actions** — unchanged from the prior AI
Assistant sprint's explicit scope boundary. **Record-level scoping**:
still module-level RBAC, consistent with every other module's current
bar.
