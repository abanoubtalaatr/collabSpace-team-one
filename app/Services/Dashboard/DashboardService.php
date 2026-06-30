<?php

namespace App\Services\Dashboard;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Dashboard\DashboardRepository;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $repository,
    ) {}

    /**
     * @return array<string, int|float>
     */
    public function stats(User $user): array
    {
        $query = $this->repository->taskScopeFor($user, $this->roleFor($user));

        $pendingTasks = (clone $query)->where('status', TaskStatus::Pending->value)->count();
        $inProgressTasks = (clone $query)->where('status', TaskStatus::InProgress->value)->count();
        $inReviewTasks = (clone $query)->where('status', TaskStatus::InReview->value)->count();
        $completedTasks = (clone $query)->where('status', TaskStatus::Completed->value)->count();
        $totalTasks = (clone $query)->count();

        return [
            'pending_tasks' => $pendingTasks,
            'in_progress_tasks' => $inProgressTasks,
            'in_review_tasks' => $inReviewTasks,
            'completed_tasks' => $completedTasks,
            'total_tasks' => $totalTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
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
     * @return array<int, array{month: string, total_tasks: int, completed_tasks: int}>
     */
    public function projectOverview(User $user, int $projectId): array
    {
        $role = $this->roleFor($user);
        $project = $this->projectOrFail($projectId);

        if (! $this->repository->userCanAccessProject($user, $role, $project)) {
            throw new AccessDeniedHttpException('You are not authorized to access this project dashboard.');
        }

        $tasks = $this->repository->overviewTasks($user, $role, $project);

        return collect(range(1, 12))
            ->map(fn (int $month): array => $this->monthOverview($tasks, $month))
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

        if ($user->hasRole('member') || $user->hasRole('Member')) {
            return 'member';
        }

        throw new AccessDeniedHttpException('User does not have a supported dashboard role.');
    }

    private function projectOrFail(int $projectId): Project
    {
        $project = $this->repository->findProject($projectId);

        if (! $project instanceof Project) {
            throw new NotFoundHttpException('Project not found.');
        }

        return $project;
    }

    /**
     * @param  Collection<int, mixed>  $tasks
     * @return array{month: string, total_tasks: int, completed_tasks: int}
     */
    private function monthOverview(Collection $tasks, int $month): array
    {
        $monthTasks = $tasks->filter(fn ($task): bool => (int) $task->created_at->month === $month);

        return [
            'month' => now()->startOfYear()->month($month)->format('M'),
            'total_tasks' => $monthTasks->count(),
            'completed_tasks' => $monthTasks
                ->filter(fn ($task): bool => $this->statusValue($task->status) === TaskStatus::Completed->value)
                ->count(),
        ];
    }

    private function statusValue(mixed $status): string
    {
        if ($status instanceof TaskStatus) {
            return $status->value;
        }

        return (string) $status;
    }
}
