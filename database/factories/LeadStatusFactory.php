<?php

namespace Database\Factories;

use App\Models\LeadStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadStatusFactory extends Factory
{
    protected $model = LeadStatus::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name_en' => $this->faker->unique()->randomElement(['New', 'Contacted', 'Qualified', 'Proposal Sent', 'Negotiation', 'Won', 'Lost']),
            'color' => $this->faker->hexColor(),
            'is_won' => false,
            'is_lost' => false,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function won(): static
    {
        return $this->state(['is_won' => true, 'name_en' => 'Won']);
    }

    public function lost(): static
    {
        return $this->state(['is_lost' => true, 'name_en' => 'Lost']);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
