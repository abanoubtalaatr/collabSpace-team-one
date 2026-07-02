<?php

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\AssignProjectTeamsRequest;
use App\Http\Requests\Project\RemoveProjectTeamsRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TeamResource;
use App\Models\Project;
use App\Models\Team;
use App\Services\ChatService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectTeamController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $project->load([
            'teams' => fn ($query) => $query
                ->with([
                    'members.media',
                    'projects:id,name,status,priority,start_date,deadline',
                ])
                ->withCount(['members', 'projects']),
        ]);

        return TeamResource::collection($project->teams);
    }

    public function store(AssignProjectTeamsRequest $request, Project $project): ProjectResource
    {
        $this->authorizeProjectManagement($request, $project);

        $teamIds = $request->validated('team_ids');
        $existingTeamIds = $project->teams()->pluck('teams.id')->all();

        $project->teams()->syncWithoutDetaching($teamIds);

        Team::query()
            ->whereIn('id', array_diff($teamIds, $existingTeamIds))
            ->with('members')
            ->get()
            ->each(fn (Team $team) => $this->notifications->notifyProjectTeamAdded($request->user(), $project, $team));

        $this->refreshProjectChatParticipants($project);

        $project->load(['creator', 'media', 'teams:id,name,display_name,description,created_at,updated_at']);

        return new ProjectResource($project);
    }

    public function destroy(RemoveProjectTeamsRequest $request, Project $project): ProjectResource
    {
        $this->authorizeProjectManagement($request, $project);

        $teamIds = $request->validated('team_ids');

        Team::query()
            ->whereIn('id', $teamIds)
            ->with('members')
            ->get()
            ->each(fn (Team $team) => $this->notifications->notifyProjectTeamRemoved($request->user(), $project, $team));

        $project->teams()->detach($teamIds);
        $this->refreshProjectChatParticipants($project);

        $project->load(['creator', 'media', 'teams:id,name,display_name,description,created_at,updated_at']);

        return new ProjectResource($project);
    }

    public function removeOne(Request $request, Project $project, int $teamId): JsonResponse
    {
        $this->authorizeProjectManagement($request, $project);

        $team = Team::with('members')->findOrFail($teamId);

        if ($project->teams()->whereKey($teamId)->exists()) {
            $this->notifications->notifyProjectTeamRemoved($request->user(), $project, $team);
        }

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
