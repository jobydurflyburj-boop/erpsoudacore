<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'category' => $this->faker->randomElement(['user.invited', 'role.changed', 'task.assigned']),
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->optional()->sentence(10),
            'data' => [],
        ];
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }
}
