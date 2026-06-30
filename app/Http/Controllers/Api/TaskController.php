<?php

namespace App\Http\Controllers\Api;

use App\Actions\Task\CreateTaskAction;
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
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = Task::query()
            ->with(['project', 'users'])
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
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

    public function show(int $id): TaskResource
    {
        $task = Task::with(['project', 'users'])->findOrFail($id);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, int $id): TaskResource
    {
        $task = Task::findOrFail($id);

        $task->update($request->safe()->except('user_ids'));

        if ($request->has('user_ids')) {
            $task->users()->sync($request->input('user_ids'));
        }

        $task->load(['project', 'users']);

        return new TaskResource($task);
    }

    public function destroy(int $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully.']);
    }
}
