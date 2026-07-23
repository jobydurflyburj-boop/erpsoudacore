<?php

namespace Database\Factories;

use App\Models\OpportunityStage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpportunityStageFactory extends Factory
{
    protected $model = OpportunityStage::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name_en' => $this->faker->unique()->randomElement(['Qualification', 'Needs Analysis', 'Proposal', 'Negotiation', 'Closed Won', 'Closed Lost']),
            'color' => $this->faker->hexColor(),
            'default_probability' => $this->faker->numberBetween(0, 100),
            'is_won' => false,
            'is_lost' => false,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function won(): static
    {
        return $this->state(['is_won' => true, 'default_probability' => 100, 'name_en' => 'Closed Won']);
    }

    public function lost(): static
    {
        return $this->state(['is_lost' => true, 'default_probability' => 0, 'name_en' => 'Closed Lost']);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
