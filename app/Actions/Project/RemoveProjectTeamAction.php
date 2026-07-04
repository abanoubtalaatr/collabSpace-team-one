<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\NotificationService;

class RemoveProjectTeamAction
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function execute(Project $project, Team $team, User $actor): Project
    {
        $wasAssigned = $project->teams()
            ->whereKey($team->id)
            ->exists();

        if ($wasAssigned) {
            $this->notifications->notifyProjectTeamRemoved($actor, $project, $team->loadMissing('members'));
        }

        $project->teams()->detach($team->id);

        return $project->load(['teams.members']);
    }
}
