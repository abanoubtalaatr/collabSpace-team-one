<?php

namespace App\Http\Controllers\Api\Project;

use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\DeleteProjectAction;
use App\Actions\Project\UpdateProjectAction;
use App\DTOs\ProjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Project Manager — scoped CRUD.
 *
 * Permissions (from spec):
 *  index   ✅  View all projects
 *  show    ✅  View any project
 *  store   ✅  Create projects
 *  update  ✅  Update ONLY own projects  (created_by = auth user)
 *  destroy ✅  Delete ONLY own projects  (created_by = auth user)
 *
 * Supported query filters (?key=value):
 *  status, priority, start_date, deadline, search
 */
class ProjectController extends Controller
{

    public function __construct(
        private readonly ProjectService      $service,
        private readonly CreateProjectAction $createAction,
        private readonly UpdateProjectAction $updateAction,
        private readonly DeleteProjectAction $deleteAction,
    ) {}

    // -------------------------------------------------------------------------
    // Queries → Service
    // -------------------------------------------------------------------------

    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $this->service->getAllPaginated($request, perPage: 15);

        return ProjectResource::collection($projects);

    }

    public function show(int $id): ProjectResource
    {
        $project = $this->service->findOrFail($id);

        return new ProjectResource($project);
    }

    // -------------------------------------------------------------------------
    // Mutations → Actions directly
    // -------------------------------------------------------------------------

    public function store(StoreProjectRequest $request): ProjectResource
    {
        $project = $this->createAction->execute(
            ProjectDTO::fromStoreRequest($request)
        );

        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, int $id): ProjectResource
    {
        $project = $this->service->findOrFail($id);

        abort_unless(
            $project->created_by === $request->user()->id,
            403,
            'You can only update projects you created.'
        );

        $updated = $this->updateAction->execute(
            $project,
            ProjectDTO::fromUpdateRequest($request)
        );

        return new ProjectResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = $this->service->findOrFail($id);

        abort_unless(
            $project->created_by === auth()->id(),
            403,
            'You can only delete projects you created.'
        );

        $this->deleteAction->execute($project);

        return response()->json(['message' => 'Project deleted successfully.']);
    }
}