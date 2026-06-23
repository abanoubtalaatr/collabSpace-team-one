<?php

namespace App\Http\Controllers\api\admin;

use App\DTOs\ProjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Admin — Full CRUD on ALL projects.
 *
 * Permissions (from spec):
 *  index   ✅  View all projects
 *  show    ✅  View any project
 *  store   ✅  Create projects
 *  update  ✅  Update any project
 *  destroy ✅  Delete any project
 */
class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $service,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $projects = $this->service->getAllPaginated(perPage: 15);

        return ProjectResource::collection($projects);
    }

    public function show(int $id): ProjectResource
    {
        $project = $this->service->findOrFail($id);

        return new ProjectResource($project);
    }

    public function store(StoreProjectRequest $request): ProjectResource
    {
        $project = $this->service->create(
            ProjectDTO::fromStoreRequest($request)
        );

        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, int $id): ProjectResource
    {
        $project = $this->service->findOrFail($id);

        $updated = $this->service->update(
            $project,
            ProjectDTO::fromUpdateRequest($request)
        );

        return new ProjectResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = $this->service->findOrFail($id);

        $this->service->delete($project);

        return response()->json(['message' => 'Project deleted successfully.']);
    }
}