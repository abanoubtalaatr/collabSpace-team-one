<?php

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\AssignProjectTeamsRequest;
use App\Http\Requests\Project\RemoveProjectTeamsRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TeamResource;
use App\Models\Project;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectTeamController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $project->load('teams:id,name,display_name,description');

        return TeamResource::collection($project->teams);
    }

    public function store(AssignProjectTeamsRequest $request, Project $project): ProjectResource
    {
        $this->authorizeProjectManagement($request, $project);

        $project->teams()->syncWithoutDetaching($request->validated('team_ids'));
        $this->refreshProjectChatParticipants($project);

        $project->load(['creator', 'media', 'teams:id,name,display_name,description']);

        return new ProjectResource($project);
    }

    public function destroy(RemoveProjectTeamsRequest $request, Project $project): ProjectResource
    {
        $this->authorizeProjectManagement($request, $project);

        $project->teams()->detach($request->validated('team_ids'));
        $this->refreshProjectChatParticipants($project);

        $project->load(['creator', 'media', 'teams:id,name,display_name,description']);

        return new ProjectResource($project);
    }

    public function removeOne(Request $request, Project $project, int $teamId): JsonResponse
    {
        $this->authorizeProjectManagement($request, $project);

        $project->teams()->detach($teamId);
        $this->refreshProjectChatParticipants($project);

        return response()->json(['message' => 'Team removed from project successfully.']);
    }

    private function authorizeProjectManagement(Request $request, Project $project): void
    {
        $user = $request->user();

        if ($user->hasRole('admin') || $project->created_by === $user->id) {
            return;
        }

        abort(403, 'You are not allowed to manage teams for this project.');
    }

    private function refreshProjectChatParticipants(Project $project): void
    {
        if ($project->conversations()->exists()) {
            $this->chatService->findOrCreateProjectConversation($project);
        }
    }
}
