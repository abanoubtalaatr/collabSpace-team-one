<?php

namespace App\Actions\Team;

use App\Models\Team;
use App\Models\User;
use App\Services\NotificationService;

class AddTeamMemberAction
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function execute(Team $team, User $targetUser, User $actor): Team
    {
        $alreadyMember = $team->members()
            ->whereKey($targetUser->id)
            ->exists();

        $team->members()->syncWithoutDetaching([$targetUser->id]);

        if (! $alreadyMember) {
            $this->notifications->notifyTeamMemberAdded($actor, $team, $targetUser);
        }

        return $team->load('members');
    }
}
