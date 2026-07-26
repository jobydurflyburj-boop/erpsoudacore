<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Multitenancy\TenantContext;
use App\Policies\CustomerPolicy;
use App\Policies\LeadPolicy;
use App\Policies\OpportunityPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One TenantContext per request — resolved once by ResolveTenant
        // middleware, read everywhere else (models, services, middleware)
        // that needs to know "which tenant is this".
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);

        // Production Readiness — Security hardening (OWASP): a real
        // gap found during this sprint's review. The only rate limit
        // on this whole API before this sprint was the generic
        // `throttleApi('api')` 60/min-per-user default applied in
        // bootstrap/app.php — meaning login, OTP verification, and
        // password reset (all unauthenticated, all classic
        // brute-force/enumeration targets) shared that same generous
        // limit with every other endpoint. 'auth' below is
        // deliberately tighter and keyed by IP + the submitted
        // email/identifier together (not IP alone), so one attacker
        // can't exhaust a victim's attempts by spraying across many
        // source IPs, and one shared office IP with many real users
        // can't lock each other out.
                RateLimiter::for('api', function (Request $request) {
                                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
                });
                                 
                                 RateLimiter::for('auth', function (Request $request) {
            $identifier = (string) ($request->input('email') ?? $request->input('username') ?? $request->ip());

            return Limit::perMinute(10)->by($request->ip().'|'.$identifier);
        });
    }
}
