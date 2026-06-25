<?php

namespace App\Http\Controllers\Api\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\AssignTeamMembersRequest;
use App\Http\Requests\Team\RemoveTeamMembersRequest;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserSummaryResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamMemberController extends Controller
{
    public function index(Team $team): AnonymousResourceCollection
    {
        $team->load('members:id,name,email');

        return UserSummaryResource::collection($team->members);
    }

    public function store(AssignTeamMembersRequest $request, Team $team): TeamResource
    {
        $team->members()->syncWithoutDetaching($request->validated('user_ids'));

        $team->load(['members:id,name,email', 'projects:id,name'])
            ->loadCount(['members', 'projects']);

        return new TeamResource($team);
    }

    public function destroy(RemoveTeamMembersRequest $request, Team $team): TeamResource
    {
        $team->members()->detach($request->validated('user_ids'));

        $team->load(['members:id,name,email', 'projects:id,name'])
            ->loadCount(['members', 'projects']);

        return new TeamResource($team);
    }

    public function removeOne(Team $team, int $userId): JsonResponse
    {
        $team->members()->detach($userId);

        return response()->json(['message' => 'Member removed from team successfully.']);
    }
}
