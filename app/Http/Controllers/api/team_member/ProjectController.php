<?php

namespace App\Http\Controllers\api\team_member;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Team Member — read-only, scoped to assigned projects.
 *
 * Permissions (from spec):
 *  index   ✅  View projects where the member belongs to an assigned team
 *  show    ✅  View a single project (only if member's team is assigned)
 *  store   ❌  Not allowed
 *  update  ❌  Not allowed
 *  destroy ❌  Not allowed
 */
class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $service,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $projects = $this->service->getForTeamMemberPaginated(
            userId: auth()->id(),
            perPage: 15,
        );

        return ProjectResource::collection($projects);
    }

    public function show(int $id): ProjectResource
    {
        $project = $this->service->findOrFail($id);

        // Ensure the member belongs to at least one team assigned to this project
        $isMemberOfProject = $project->teams()
            ->whereHas('users', fn ($q) => $q->where('users.id', auth()->id()))
            ->exists();

        abort_unless($isMemberOfProject, 403, 'You do not have access to this project.');

        return new ProjectResource($project);
    }

    public function store(): never
    {
        abort(403, 'Team members are not allowed to create projects.');
    }

    public function update(): never
    {
        abort(403, 'Team members are not allowed to update projects.');
    }

    public function destroy(): never
    {
        abort(403, 'Team members are not allowed to delete projects.');
    }
}