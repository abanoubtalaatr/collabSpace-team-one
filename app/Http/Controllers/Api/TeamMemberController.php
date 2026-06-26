<?php

namespace App\Http\Controllers\Api;

use App\Actions\Team\AddTeamMemberAction;
use App\Actions\Team\RemoveTeamMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\AddTeamMemberRequest;
use App\Http\Resources\Api\TeamResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function __construct(
        private readonly AddTeamMemberAction $addTeamMemberAction,
        private readonly RemoveTeamMemberAction $removeTeamMemberAction,
    ) {}

    public function store(AddTeamMemberRequest $request, Team $team): TeamResource
    {
        $user = User::findOrFail($request->validated('user_id'));

        $team = $this->addTeamMemberAction->execute($team, $user, $request->user());

        return new TeamResource($team);
    }

    public function destroy(Request $request, Team $team, User $user): TeamResource
    {
        $team = $this->removeTeamMemberAction->execute($team, $user, $request->user());

        return new TeamResource($team);
    }
}
