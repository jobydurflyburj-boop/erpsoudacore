<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Multitenancy\TenantContext;
use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private function bindTenant(Tenant $tenant): void
    {
        app(TenantContext::class)->set($tenant);
        app(TenantContext::class)->apply();
    }

    public function test_first_call_starts_at_one(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $this->bindTenant($tenant);

        $value = app(SequenceService::class)->next($tenant->id, 'lead_number', 'LD');

        $this->assertEquals('LD-000001', $value);
    }

    public function test_repeated_calls_increment(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $this->bindTenant($tenant);

        $service = app(SequenceService::class);

        $this->assertEquals('LD-000001', $service->next($tenant->id, 'lead_number', 'LD'));
        $this->assertEquals('LD-000002', $service->next($tenant->id, 'lead_number', 'LD'));
        $this->assertEquals('LD-000003', $service->next($tenant->id, 'lead_number', 'LD'));
    }

    public function test_different_sequence_names_are_independent(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $this->bindTenant($tenant);

        $service = app(SequenceService::class);

        $this->assertEquals('LD-000001', $service->next($tenant->id, 'lead_number', 'LD'));
        $this->assertEquals('INV-000001', $service->next($tenant->id, 'invoice_number', 'INV'));
    }

    public function test_pad_length_is_configurable(): void
    {
        $tenant = Tenant::factory()->active()->create();
        $this->bindTenant($tenant);

        $value = app(SequenceService::class)->next($tenant->id, 'lead_number', 'LD', 3);

        $this->assertEquals('LD-001', $value);
    }
}
