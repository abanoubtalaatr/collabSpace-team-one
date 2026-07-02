<?php

namespace App\Http\Controllers\Api;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function __construct(
        private readonly CreateTaskAction $createTaskAction,
        private readonly UpdateTaskAction $updateTaskAction,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = Task::query()
            ->with(['project', 'users'])
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('team_id'), fn ($query) => $query->forTeam($request->integer('team_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request): TaskResource
    {
        $task = $this->createTaskAction->execute(
            $request->validated(),
            $request->user()
        );

        return new TaskResource($task);
    }

    public function show(Task $task): TaskResource
    {
        $task->load(['project', 'users']);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $updatedTask = $this->updateTaskAction->execute($task, $request->validated());

        return new TaskResource($updatedTask);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully.']);
    }
}
