<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreTaskRequest;
use App\Http\Requests\Platform\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;

/**
 * Personal productivity list — every authenticated user manages their
 * own tasks, no RBAC gate beyond authentication (same class of feature
 * as the Notification Center inbox: it's "mine", not a company-wide
 * resource). index() is always scoped to the caller; there is no
 * cross-user task visibility in this pass.
 */
class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function index(Request $request)
    {
        $tasks = Task::where('assigned_to_user_id', $request->user()->id)
            ->orderByRaw("due_at IS NULL, due_at ASC")
            ->paginate((int) $request->integer('page_size', 25));

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request)
    {
        $task = $this->taskService->create($request->user(), $request->validated());

        return $this->ok(new TaskResource($task->load(['assignee', 'creator'])), 201);
    }

    public function show(Task $task)
    {
        return $this->ok(new TaskResource($task->load(['assignee', 'creator'])));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        return $this->ok(new TaskResource($this->taskService->update($task, $request->validated())->load(['assignee', 'creator'])));
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->json(null, 204);
    }
}
