<?php

namespace App\Services\Dashboard;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Dashboard\DashboardRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function stats(User $user): array
    {
        $role = $this->roleFor($user);
        $query = $this->repository->taskScopeFor($user, $role);

        return $this->buildStatsPayload($user, $query);
    }

    /**
     * Full overview tab payload: user, avatars, charts, files, team members.
     *
     * @return array<string, mixed>
     */
    public function overview(User $user, ?int $projectId = null): array
    {
        $role = $this->roleFor($user);
        $projectId = $projectId ?? $user->current_project_id;
        $project = null;

        if ($projectId) {
            $project = $this->projectOrFail($projectId);

            if (! $this->repository->userCanAccessProject($user, $role, $project)) {
                abort(403, 'You are not authorized to access this project dashboard.');
            }

            $query = $this->repository->taskScopeForProject($user, $role, $project);
            $recentFiles = $this->repository->recentFilesForProject($project, $user);
            $teamMembers = $this->mapTeamMembers($this->repository->projectTeamMembers($project));
        } else {
            $query = $this->repository->taskScopeFor($user, $role);
            $recentFiles = $this->repository->recentFiles($user, $role);
            $teamMembers = [];
        }

        $statsPayload = $this->buildStatsPayload($user, $query);

        return [
            'user' => $statsPayload['user'],
            'project' => $project ? $this->mapProject($project, $statsPayload['progress']) : null,
            'stats' => [
                'pending_tasks' => $statsPayload['pending_tasks'],
                'in_progress_tasks' => $statsPayload['in_progress_tasks'],
                'in_review_tasks' => $statsPayload['in_review_tasks'],
                'completed_tasks' => $statsPayload['completed_tasks'],
                'total_tasks' => $statsPayload['total_tasks'],
                'progress' => $statsPayload['progress'],
                'completion_rate' => $statsPayload['completion_rate'],
            ],
            'chart_data' => $statsPayload['chart_data'],
            'recent_files' => $recentFiles,
            'team_members' => $teamMembers,
        ];
    }

    /**
     * @return Collection<int, mixed>
     */
    public function recentFiles(User $user): Collection
    {
        return $this->repository->recentFiles($user, $this->roleFor($user));
    }

    /**
     * @return array<string, mixed>
     */
    public function projectOverview(User $user, int $projectId): array
    {
        return $this->overview($user, $projectId);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatsPayload(User $user, Builder $query): array
    {
        $pendingTasks = (clone $query)->where('status', TaskStatus::Pending->value)->count();
        $inProgressTasks = (clone $query)->where('status', TaskStatus::InProgress->value)->count();
        $inReviewTasks = (clone $query)->where('status', TaskStatus::InReview->value)->count();
        $completedTasks = (clone $query)->where('status', TaskStatus::Completed->value)->count();
        $totalTasks = (clone $query)->count();

        $tasks = (clone $query)
            ->select(['id', 'status', 'progress', 'created_at'])
            ->whereYear('created_at', now()->year)
            ->get();

        $averageProgress = $tasks->isEmpty()
            ? 0
            : round((float) $tasks->avg('progress'), 2);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatarUrl(),
            ],
            'pending_tasks' => $pendingTasks,
            'in_progress_tasks' => $inProgressTasks,
            'in_review_tasks' => $inReviewTasks,
            'completed_tasks' => $completedTasks,
            'total_tasks' => $totalTasks,
            'progress' => $averageProgress,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            'chart_data' => [
                'status' => $this->statusChartData($pendingTasks, $inProgressTasks, $inReviewTasks, $completedTasks),
                'monthly' => $this->monthlyChartData($tasks),
            ],
        ];
    }

    /**
     * @return array{id: int, name: string, status: mixed, progress: float}
     */
    private function mapProject(Project $project, float $progress): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'status' => $project->status,
            'progress' => $progress,
        ];
    }

    /**
     * @param  Collection<int, User>  $members
     * @return array<int, array{id: int, name: string, email: string, job_title: string|null, avatar_url: string}>
     */
    private function mapTeamMembers(Collection $members): array
    {
        return $members->map(fn (User $member): array => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'job_title' => $member->job_title,
            'avatar_url' => $member->avatarUrl(),
        ])->values()->all();
    }

    /**
     * @return array<int, array{label: string, key: string, value: int}>
     */
    private function statusChartData(int $pending, int $inProgress, int $inReview, int $completed): array
    {
        return [
            ['label' => 'Pending', 'key' => TaskStatus::Pending->value, 'value' => $pending],
            ['label' => 'In Progress', 'key' => TaskStatus::InProgress->value, 'value' => $inProgress],
            ['label' => 'In Review', 'key' => TaskStatus::InReview->value, 'value' => $inReview],
            ['label' => 'Completed', 'key' => TaskStatus::Completed->value, 'value' => $completed],
        ];
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return array<int, array{month: string, month_number: int, total_tasks: int, completed_tasks: int, progress: float}>
     */
    private function monthlyChartData(Collection $tasks): array
    {
        return collect(range(1, 12))
            ->map(function (int $month) use ($tasks): array {
                $monthTasks = $tasks->filter(fn ($task): bool => (int) $task->created_at->month === $month);

                return [
                    'month' => now()->startOfYear()->month($month)->format('M'),
                    'month_number' => $month,
                    'total_tasks' => $monthTasks->count(),
                    'completed_tasks' => $monthTasks
                        ->filter(fn ($task): bool => $this->statusValue($task->status) === TaskStatus::Completed->value)
                        ->count(),
                    'progress' => $monthTasks->isEmpty()
                        ? 0
                        : round((float) $monthTasks->avg('progress'), 2),
                ];
            })
            ->values()
            ->all();
    }

    private function roleFor(User $user): string
    {
        if ($user->hasRole('admin')) {
            return 'admin';
        }

        if ($user->hasRole('Project')) {
            return 'project';
        }

        if ($user->hasRole(['member', 'Member'])) {
            return 'member';
        }

        return 'user';
    }

    private function projectOrFail(int $projectId): Project
    {
        $project = $this->repository->findProject($projectId);

        if (! $project instanceof Project) {
            throw new NotFoundHttpException('Project not found.');
        }

        return $project;
    }

    private function statusValue(mixed $status): string
    {
        if ($status instanceof TaskStatus) {
            return $status->value;
        }

        return (string) $status;
    }
}
