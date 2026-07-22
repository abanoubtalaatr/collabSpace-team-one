<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function delete(User $user, Task $task): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $task->loadMissing('project');

        if ($task->project && (int) $task->project->created_by === (int) $user->id) {
            return true;
        }

        // Assignees can update their work, but only creators/admins may delete.
        return false;
    }
}
