<?php

namespace App\Http\Controllers\Api\Team;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

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

     public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $this->service->getForTeamMemberPaginated($request,auth()->id(), perPage: 15);

        return ProjectResource::collection($projects);
    }

    public function show(int $id): ProjectResource
    {
        $project = $this->service->findOrFail($id);

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