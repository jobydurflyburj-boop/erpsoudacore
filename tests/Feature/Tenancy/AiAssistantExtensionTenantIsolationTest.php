<?php

namespace Tests\Feature\Tenancy;

use App\Models\AiSetting;
use App\Multitenancy\TenantContext;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiAssistantExtensionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function registerTenant(string $subdomain): array
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'AI Ext Isolation Co', 'subdomain' => $subdomain,
            'admin_full_name' => 'Owner', 'admin_email' => "owner@{$subdomain}.test",
            'admin_password' => 'a-strong-unique-passphrase',
        ]);

        return [$result['tenant'], $result['user']];
    }

    public function test_ai_settings_are_invisible_across_tenants_even_via_raw_query(): void
    {
        [$tenantA] = $this->registerTenant('ai-ext-iso-a');
        [$tenantB] = $this->registerTenant('ai-ext-iso-b');

        $context = app(TenantContext::class);
        $context->set($tenantB);
        $context->apply();

        AiSetting::create(['tenant_id' => $tenantB->id, 'provider_override' => 'openai']);

        $context->set($tenantA);
        $context->apply();

        $rows = DB::table('ai_settings')->where('tenant_id', $tenantB->id)->get();
        $this->assertCount(0, $rows);

        $context->reset();
    }

    public function test_a_provider_override_on_one_tenant_never_affects_another_tenant(): void
    {
        [$tenantA, $ownerA] = $this->registerTenant('ai-ext-iso-override-a');
        [$tenantB, $ownerB] = $this->registerTenant('ai-ext-iso-override-b');

        $context = app(TenantContext::class);

        $context->set($tenantA);
        $context->apply();
        app(\App\Services\AiSettingsService::class)->update($ownerA, ['provider_override' => 'openai']);

        $context->set($tenantB);
        $context->apply();
        $settingB = app(\App\Services\AiSettingsService::class)->get($tenantB->id);

        $context->reset();

        $this->assertNull($settingB->provider_override);
    }
}
