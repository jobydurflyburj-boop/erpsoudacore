<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'name_en' => $this->faker->words(2, true),
            'unit' => 'pcs',
            'cost_price' => $this->faker->randomFloat(2, 5, 500),
            'sale_price' => $this->faker->randomFloat(2, 10, 800),
            'vat_rate' => 15.00,
            'reorder_point' => $this->faker->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
