<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;

class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly NotificationService $notifications,
    ) {}

    public function create(User $creator, array $data): Task
    {
        $assigneeId = $data['assigned_to_user_id'] ?? $creator->id;

        $task = $this->tasks->create(array_merge($data, [
            'tenant_id' => $creator->tenant_id,
            'assigned_to_user_id' => $assigneeId,
            'created_by_user_id' => $creator->id,
            'priority' => $data['priority'] ?? 'normal',
            'status' => Task::STATUS_PENDING,
        ]));

        if ($assigneeId !== $creator->id) {
            $assignee = User::find($assigneeId);

            if ($assignee) {
                $this->notifications->send(
                    $assignee,
                    'task.assigned',
                    "New task: {$task->title}",
                    $task->description,
                    ['task_id' => $task->id]
                );
            }
        }

        return $task;
    }

    public function update(Task $task, array $data): Task
    {
        if (($data['status'] ?? null) === Task::STATUS_COMPLETED && $task->status !== Task::STATUS_COMPLETED) {
            $data['completed_at'] = now();
        }

        return $this->tasks->update($task, $data);
    }
}
