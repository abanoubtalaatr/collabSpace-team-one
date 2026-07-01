<?php

namespace App\Http\Controllers\Api\Project;

use App\Actions\Task\CreateTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreProjectTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectTaskController extends Controller
{
    public function __construct(
        private readonly CreateTaskAction $createTaskAction,
    ) {}

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->with(['project', 'users'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return TaskResource::collection($tasks);
    }

    public function store(StoreProjectTaskRequest $request, Project $project): TaskResource
    {
        $data = array_merge($request->validated(), [
            'project_id' => $project->id,
        ]);

        $task = $this->createTaskAction->execute($data, $request->user());

        return new TaskResource($task);
    }
}
