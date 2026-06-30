<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\NotificationService;

class AddProjectTeamAction
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function execute(Project $project, Team $team, User $actor): Project
    {
        $alreadyAssigned = $project->teams()
            ->whereKey($team->id)
            ->exists();

        $project->teams()->syncWithoutDetaching([$team->id]);

        if (! $alreadyAssigned) {
            $this->notifications->notifyProjectTeamAdded($actor, $project, $team->loadMissing('members'));
        }

        return $project->load(['teams.members']);
    }
}
