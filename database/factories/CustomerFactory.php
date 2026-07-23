<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'customer_number' => 'CU-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_type' => $this->faker->randomElement(['company', 'individual']),
            'company_name' => $this->faker->company(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('05########'),
            'country' => 'SA',
            'city' => $this->faker->city(),
            'status' => Customer::STATUS_ACTIVE,
            'credit_limit' => $this->faker->randomFloat(2, 0, 100000),
            'payment_terms_days' => $this->faker->randomElement([0, 15, 30, 60]),
        ];
    }
}
