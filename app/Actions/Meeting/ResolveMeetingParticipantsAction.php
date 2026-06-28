<?php

namespace App\Actions\Meeting;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class ResolveMeetingParticipantsAction
{
    /**
     * @param  array<int, int>  $userIds
     * @param  array<int, int>  $teamIds
     * @return Collection<int, User>
     */
    public function execute(array $userIds, array $teamIds, int $creatorId): Collection
    {
        $participantIds = collect($userIds)->unique()->values();

        if ($teamIds !== []) {
            $teamMemberIds = Team::query()
                ->whereIn('id', $teamIds)
                ->with('members:id')
                ->get()
                ->flatMap(fn (Team $team) => $team->members->pluck('id'));

            $participantIds = $participantIds->merge($teamMemberIds)->unique()->values();
        }

        $participantIds = $participantIds->push($creatorId)->unique()->values();

        return User::query()->whereIn('id', $participantIds)->get();
    }
}
