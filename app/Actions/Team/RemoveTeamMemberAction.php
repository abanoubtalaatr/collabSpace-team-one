<?php

namespace App\Actions\Team;

use App\Models\Team;
use App\Models\User;
use App\Services\NotificationService;

class RemoveTeamMemberAction
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function execute(Team $team, User $targetUser, User $actor): Team
    {
        $wasMember = $team->members()
            ->whereKey($targetUser->id)
            ->exists();

        $team->members()->detach($targetUser->id);

        if ($wasMember) {
            $this->notifications->notifyTeamMemberRemoved($actor, $team, $targetUser);
        }

        return $team->load('members');
    }
}
