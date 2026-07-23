<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'legal_name' => $this->faker->company().' LLC',
            'legal_name_ar' => null,
            'trade_name' => $this->faker->company(),
            'cr_number' => (string) $this->faker->numerify('##########'),
            'vat_number' => (string) $this->faker->numerify('###############'),
            'national_address' => $this->faker->address(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->numerify('05########'),
            'website' => $this->faker->url(),
            'timezone' => 'Asia/Riyadh',
            'currency' => 'SAR',
            'language' => 'ar',
            'fiscal_year_start_month' => 1,
            'business_type' => $this->faker->randomElement(['retail', 'services', 'manufacturing', 'trading']),
            'is_default' => true,
        ];
    }
}
