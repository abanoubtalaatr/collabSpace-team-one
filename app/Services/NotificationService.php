<?php

namespace App\Services;

use App\DTOs\NotificationData;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CollaborationDatabaseNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notifyTaskCreated(User $actor, Task $task, Collection|EloquentCollection $recipients): void
    {
        $this->notifyUsers(
            $actor,
            $recipients,
            'task_created',
            'Task created',
            sprintf('A new task "%s" was created.', $task->title),
            'task',
            (int) $task->id
        );
    }

    public function notifyTeamMemberAdded(User $actor, Team $team, User $targetUser): void
    {
        $this->notifyUser(
            $actor,
            $targetUser,
            'team_member_added',
            'Added to team',
            sprintf('You were added to the team "%s".', $team->name),
            'team',
            (int) $team->id
        );
    }

    public function notifyTeamMemberRemoved(User $actor, Team $team, User $targetUser): void
    {
        $this->notifyUser(
            $actor,
            $targetUser,
            'team_member_removed',
            'Removed from team',
            sprintf('You were removed from the team "%s".', $team->name),
            'team',
            (int) $team->id
        );
    }

    public function notifyProjectTeamAdded(User $actor, Project $project, Team $team): void
    {
        $this->notifyUsers(
            $actor,
            $team->members,
            'project_member_added',
            'Added to project',
            sprintf('You were added to the project "%s".', $project->name),
            'project',
            (int) $project->id
        );
    }

    public function notifyProjectTeamRemoved(User $actor, Project $project, Team $team): void
    {
        $this->notifyUsers(
            $actor,
            $team->members,
            'project_member_removed',
            'Removed from project',
            sprintf('You were removed from the project "%s".', $project->name),
            'project',
            (int) $project->id
        );
    }

    public function notifyProjectStatusUpdated(User $actor, Project $project, string $oldStatus, string $newStatus): void
    {
        $recipients = $project->teams()
            ->with('members')
            ->get()
            ->flatMap(fn (Team $team): EloquentCollection => $team->members)
            ->push($project->creator)
            ->filter();

        $this->notifyUsers(
            $actor,
            $recipients,
            'project_status_updated',
            'Project status updated',
            sprintf('Project "%s" status changed from %s to %s.', $project->name, $oldStatus, $newStatus),
            'project',
            (int) $project->id,
            $oldStatus,
            $newStatus
        );
    }

    private function notifyUser(
        User $actor,
        User $targetUser,
        string $type,
        string $title,
        string $message,
        string $entityType,
        int $entityId,
        ?string $oldStatus = null,
        ?string $newStatus = null,
    ): void {
        if ($targetUser->is($actor)) {
            return;
        }

        $targetUser->notify(new CollaborationDatabaseNotification(new NotificationData(
            type: $type,
            title: $title,
            message: $message,
            actorId: (int) $actor->id,
            targetUserId: (int) $targetUser->id,
            entityType: $entityType,
            entityId: $entityId,
            oldStatus: $oldStatus,
            newStatus: $newStatus,
        )));
    }

    private function notifyUsers(
        User $actor,
        Collection|EloquentCollection $recipients,
        string $type,
        string $title,
        string $message,
        string $entityType,
        int $entityId,
        ?string $oldStatus = null,
        ?string $newStatus = null,
    ): void {
        $recipients
            ->filter(fn (User $user): bool => ! $user->is($actor))
            ->unique('id')
            ->each(fn (User $user): mixed => $this->notifyUser(
                $actor,
                $user,
                $type,
                $title,
                $message,
                $entityType,
                $entityId,
                $oldStatus,
                $newStatus,
            ));
    }
}
