<?php

namespace App\Http\Controllers\Api;

use App\Actions\Project\AddProjectTeamAction;
use App\Actions\Project\RemoveProjectTeamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\AddProjectTeamRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;

class ProjectTeamController extends Controller
{
    public function __construct(
        private readonly AddProjectTeamAction $addProjectTeamAction,
        private readonly RemoveProjectTeamAction $removeProjectTeamAction,
    ) {}

    public function store(AddProjectTeamRequest $request, Project $project): ProjectResource
    {
        $team = Team::findOrFail($request->validated('team_id'));

        $project = $this->addProjectTeamAction->execute($project, $team, $request->user());

        return new ProjectResource($project);
    }

    public function destroy(Request $request, Project $project, Team $team): ProjectResource
    {
        $project = $this->removeProjectTeamAction->execute($project, $team, $request->user());

        return new ProjectResource($project);
    }
}
