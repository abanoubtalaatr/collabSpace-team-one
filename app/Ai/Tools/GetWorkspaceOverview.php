<?php

namespace App\Ai\Tools;

use App\Ai\Concerns\ScopesToUser;
use App\Enums\TaskStatus;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetWorkspaceOverview implements Tool
{
    use ScopesToUser;

    public function description(): Stringable|string
    {
        return 'Get a summary dashboard for the current user: task counts by status, teams, projects, and profile info.';
    }

    public function handle(Request $request): string
    {
        $taskCounts = $this->user->tasks()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $accessibleProjects = Project::query()
            ->where(function ($q) {
                $q->where('created_by', $this->user->id)
                    ->orWhereHas('teams.members', fn ($m) => $m->where('users.id', $this->user->id));
            })
            ->count();

        $overview = [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'job_title' => $this->user->job_title,
                'availability_status' => $this->user->availability_status?->value ?? $this->user->availability_status,
            ],
            'tasks' => [
                'pending' => (int) ($taskCounts[TaskStatus::Pending->value] ?? 0),
                'in_progress' => (int) ($taskCounts[TaskStatus::InProgress->value] ?? 0),
                'in_review' => (int) ($taskCounts[TaskStatus::InReview->value] ?? 0),
                'completed' => (int) ($taskCounts[TaskStatus::Completed->value] ?? 0),
                'total' => $this->user->tasks()->count(),
            ],
            'teams_count' => $this->user->teams()->count(),
            'projects_created_count' => $this->user->projects()->count(),
            'accessible_projects_count' => $accessibleProjects,
        ];

        return json_encode($overview, JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
