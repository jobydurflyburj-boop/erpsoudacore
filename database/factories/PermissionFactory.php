<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $module = $this->faker->randomElement(['admin', 'core']);
        $action = $this->faker->randomElement(['view', 'create', 'edit', 'delete']);

        return [
            'module' => $module,
            'action' => $action,
            'name' => "{$module}.{$action}.".$this->faker->unique()->word(),
            'label' => ucfirst($action).' '.ucfirst($module),
        ];
    }
}
