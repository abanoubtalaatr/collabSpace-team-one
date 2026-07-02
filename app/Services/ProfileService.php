<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Message;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class ProfileService
{
    public function getProfile(User $user): User
    {
        return User::query()
            ->whereKey($user->getKey())
            ->with([
                'teams:id,name,display_name',
                'currentTeam:id,name,display_name',
                'currentProject:id,name,status',
                'roles:id,name,display_name',
                'media',
            ])
            ->withCount(['tasks', 'teams', 'projects'])
            ->firstOrFail();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRecentActivity(User $user, int $limit = 15): Collection
    {
        $activities = collect();

        $user->tasks()
            ->with('project:id,name')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->each(function (Task $task) use ($activities): void {
                $activities->push([
                    'type' => 'task',
                    'action' => $task->status === TaskStatus::Completed ? 'completed' : 'updated',
                    'title' => $task->title,
                    'description' => 'Task in project: '.($task->project?->name ?? 'N/A'),
                    'status' => $task->status?->value,
                    'occurred_at' => $task->updated_at?->toDateTimeString(),
                    'meta' => [
                        'task_id' => $task->id,
                        'project_id' => $task->project_id,
                    ],
                ]);
            });

        Message::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->each(function (Message $message) use ($activities): void {
                $activities->push([
                    'type' => 'message',
                    'action' => 'sent',
                    'title' => 'Sent a message',
                    'description' => str($message->body)->limit(80)->value(),
                    'status' => null,
                    'occurred_at' => $message->created_at?->toDateTimeString(),
                    'meta' => [
                        'message_id' => $message->id,
                        'conversation_id' => $message->conversation_id,
                    ],
                ]);
            });

        $user->projects()
            ->latest()
            ->limit(5)
            ->get()
            ->each(function (Project $project) use ($activities): void {
                $activities->push([
                    'type' => 'project',
                    'action' => 'created',
                    'title' => $project->name,
                    'description' => 'Created a new project',
                    'status' => $project->status?->value ?? $project->status,
                    'occurred_at' => $project->created_at?->toDateTimeString(),
                    'meta' => [
                        'project_id' => $project->id,
                    ],
                ]);
            });

        return $activities
            ->sortByDesc('occurred_at')
            ->take($limit)
            ->values();
    }

    /**
     * @return array<string, int>
     */
    public function getTaskSummary(User $user): array
    {
        $counts = $user->tasks()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'pending' => (int) ($counts[TaskStatus::Pending->value] ?? 0),
            'in_progress' => (int) ($counts[TaskStatus::InProgress->value] ?? 0),
            'in_review' => (int) ($counts[TaskStatus::InReview->value] ?? 0),
            'completed' => (int) ($counts[TaskStatus::Completed->value] ?? 0),
        ];

        $summary['total'] = array_sum($summary);
        $summary['to_do'] = $summary['pending'];
        $summary['done'] = $summary['completed'];

        return $summary;
    }
}
