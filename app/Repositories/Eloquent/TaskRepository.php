<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;

class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    protected string $modelClass = Task::class;

    protected array $allowedFilters = ['status', 'priority', 'assigned_to_user_id'];

    protected array $allowedSorts = ['created_at', 'due_at', 'priority'];

    protected array $searchableFields = ['title', 'description'];

    protected string $defaultSort = 'due_at';
}
