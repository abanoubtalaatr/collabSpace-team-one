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
     * @param  array{project_id: int, name: string, description?: string|null, status?: string|null, user_ids?: array<int, int>}  $data
     */
    public function execute(array $data, User $actor): Task
    {
        $userIds = $data['user_ids'] ?? [];

        $task = Task::create([
            'project_id' => $data['project_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? TaskStatus::Pending->value,
        ]);

        if ($userIds !== []) {
            $task->users()->syncWithoutDetaching($userIds);
        }

        $task->load(['project', 'users']);

        $this->notifications->notifyTaskCreated($actor, $task, $task->users);

        return $task;
    }
}
