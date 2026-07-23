<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $company = Company::factory()->create();

        return [
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'name_en' => $this->faker->randomElement(['Sales', 'Finance', 'Operations', 'Human Resources', 'Warehouse']),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
