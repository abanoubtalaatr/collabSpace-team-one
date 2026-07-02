<?php

namespace App\Http\Controllers\Api\Team;

use App\Actions\Team\AddTeamMemberAction;
use App\Actions\Team\RemoveTeamMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\AssignTeamMembersRequest;
use App\Http\Requests\Team\RemoveTeamMembersRequest;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserSummaryResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamMemberController extends Controller
{
    public function __construct(
        private readonly AddTeamMemberAction $addTeamMemberAction,
        private readonly RemoveTeamMemberAction $removeTeamMemberAction,
    ) {}

    public function index(Team $team): AnonymousResourceCollection
    {
        $team->load('members:id,name,email');

        return UserSummaryResource::collection($team->members);
    }

    public function store(AssignTeamMembersRequest $request, Team $team): TeamResource
    {
        foreach ($request->validated('user_ids') as $userId) {
            $user = User::findOrFail($userId);
            $team = $this->addTeamMemberAction->execute($team, $user, $request->user());
        }

        $team->load(['members.media', 'projects:id,name,status,priority,start_date,deadline'])
            ->loadCount(['members', 'projects']);

        return new TeamResource($team);
    }

    public function destroy(RemoveTeamMembersRequest $request, Team $team): TeamResource
    {
        foreach ($request->validated('user_ids') as $userId) {
            $user = User::findOrFail($userId);
            $team = $this->removeTeamMemberAction->execute($team, $user, $request->user());
        }

        $team->load(['members.media', 'projects:id,name,status,priority,start_date,deadline'])
            ->loadCount(['members', 'projects']);

        return new TeamResource($team);
    }

    public function removeOne(Team $team, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $this->removeTeamMemberAction->execute($team, $user, request()->user());

        return response()->json(['message' => 'Member removed from team successfully.']);
    }
}
