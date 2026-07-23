<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'tenant_id' => $user->tenant_id,
            'assigned_to_user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high']),
            'status' => Task::STATUS_PENDING,
            'due_at' => $this->faker->optional()->dateTimeBetween('now', '+2 weeks'),
        ];
    }

    public function overdue(): static
    {
        return $this->state(['due_at' => now()->subDays(3), 'status' => Task::STATUS_PENDING]);
    }

    public function completed(): static
    {
        return $this->state(['status' => Task::STATUS_COMPLETED, 'completed_at' => now()]);
    }
}
