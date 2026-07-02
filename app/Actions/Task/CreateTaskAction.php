<?php

namespace App\Actions\Task;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;

class CreateTaskAction
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor): Task
    {
        $userIds = $data['user_ids'] ?? [];

        $task = Task::create([
            'project_id' => $data['project_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'due_date' => $data['due_date'] ?? now()->addWeek()->toDateString(),
            'progress' => $data['progress'] ?? 0,
            'status' => $data['status'] ?? TaskStatus::Pending->value,
            'priority' => $data['priority'],
        ]);

        if ($userIds !== []) {
            $task->users()->syncWithoutDetaching($userIds);
        }

        $task->load(['project', 'users.media']);

        $this->notifications->notifyTaskCreated($actor, $task, $task->users);

        return $task;
    }
}
