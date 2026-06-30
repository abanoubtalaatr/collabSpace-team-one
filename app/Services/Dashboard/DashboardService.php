<?php

namespace App\Services\Dashboard;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Dashboard\DashboardRepository;
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
     * @return Collection<int, mixed>
     */
    public function recentFiles(User $user): Collection
    {
        return $this->repository->recentFiles($user, $this->roleFor($user));
    }

    /**
     * @return array<int, array{month: string, total_tasks: int, completed_tasks: int, progress: float}>
     */
    public function projectOverview(User $user, int $projectId): array
    {
        $role = $this->roleFor($user);
        $project = $this->projectOrFail($projectId);

        if (! $this->repository->userCanAccessProject($user, $role, $project)) {
            abort(403, 'You are not authorized to access this project dashboard.');
        }

        $tasks = $this->repository->overviewTasks($user, $role, $project);

        return collect(range(1, 12))
            ->map(fn (int $month): array => $this->monthOverview($tasks, $month))
            ->all();
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

    /**
     * @param  Collection<int, mixed>  $tasks
     * @return array{month: string, total_tasks: int, completed_tasks: int, progress: float}
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
            'progress' => $monthTasks->isEmpty()
                ? 0
                : round((float) $monthTasks->avg('progress'), 2),
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
