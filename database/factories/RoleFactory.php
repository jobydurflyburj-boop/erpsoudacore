<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'code' => $this->faker->unique()->slug(2, '_'),
            'name_en' => $this->faker->jobTitle(),
            'is_system_role' => false,
        ];
    }
}
