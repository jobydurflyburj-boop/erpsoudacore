<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $status = LeadStatus::factory()->for($tenant)->default()->create();

        return [
            'tenant_id' => $tenant->id,
            'lead_number' => 'LD-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'company_name' => $this->faker->company(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('05########'),
            'country' => 'SA',
            'city' => $this->faker->city(),
            'lead_status_id' => $status->id,
            'expected_revenue' => $this->faker->randomFloat(2, 1000, 500000),
            'probability' => $this->faker->numberBetween(0, 100),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
        ];
    }
}
