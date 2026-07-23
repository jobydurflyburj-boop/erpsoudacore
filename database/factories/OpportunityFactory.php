<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $stage = OpportunityStage::factory()->for($tenant)->default()->create();

        return [
            'tenant_id' => $tenant->id,
            'opportunity_number' => 'OP-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'name' => $this->faker->catchPhrase(),
            'customer_id' => $customer->id,
            'stage_id' => $stage->id,
            'amount' => $this->faker->randomFloat(2, 1000, 1000000),
            'probability' => $stage->default_probability,
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
        ];
    }
}
