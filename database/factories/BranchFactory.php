<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $company = Company::factory()->create();

        return [
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'name' => $this->faker->city().' Branch',
            'city' => $this->faker->city(),
            'phone' => $this->faker->numerify('05########'),
            'working_hours' => [
                'sun' => ['open' => '09:00', 'close' => '18:00'],
                'mon' => ['open' => '09:00', 'close' => '18:00'],
            ],
            'latitude' => $this->faker->latitude(16, 32),
            'longitude' => $this->faker->longitude(34, 55),
            'is_main' => true,
            'is_active' => true,
        ];
    }
}
