<?php

namespace Database\Factories;

use App\Models\LeadSource;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadSourceFactory extends Factory
{
    protected $model = LeadSource::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name_en' => $this->faker->unique()->randomElement(['Website', 'Referral', 'Phone Inquiry', 'Social Media', 'Trade Show', 'Cold Outreach', 'Partner']),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
