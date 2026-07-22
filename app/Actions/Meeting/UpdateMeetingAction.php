<?php

namespace App\Actions\Meeting;

use App\Enums\NotificationType;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class UpdateMeetingAction
{
    public function __construct(
        private readonly ResolveMeetingParticipantsAction $resolveParticipants,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Meeting $meeting, array $data, User $actor): Meeting
    {
        return DB::transaction(function () use ($meeting, $data, $actor) {
            $meeting->update([
                'title' => $data['title'] ?? $meeting->title,
                'description' => $data['description'] ?? $meeting->description,
                'starts_at' => $data['starts_at'] ?? $meeting->starts_at,
                'ends_at' => $data['ends_at'] ?? $meeting->ends_at,
                'meeting_link' => $data['meeting_link'] ?? $meeting->meeting_link,
                'location' => $data['location'] ?? $meeting->location,
                'project_id' => $data['project_id'] ?? $meeting->project_id,
            ]);

            if (array_key_exists('user_ids', $data) || array_key_exists('team_ids', $data)) {
                $hasExplicitUserList = array_key_exists('user_ids', $data);
                $userIds = $hasExplicitUserList
                    ? ($data['user_ids'] ?? [])
                    : $meeting->users()->pluck('users.id')->all();
                $teamIds = array_key_exists('team_ids', $data)
                    ? ($data['team_ids'] ?? [])
                    : $meeting->teams()->pluck('teams.id')->all();

                // Explicit user_ids is the final participant list — do not re-expand teams into users.
                $participantTeamIds = $hasExplicitUserList ? [] : $teamIds;

                $participants = $this->resolveParticipants->execute(
                    $userIds,
                    $participantTeamIds,
                    $meeting->created_by,
                );

                $meeting->users()->sync($participants->pluck('id'));

                if (array_key_exists('team_ids', $data)) {
                    $meeting->teams()->sync($teamIds);
                }
            }

            $notifyUsers = $meeting->users()->where('users.id', '!=', $actor->id)->get();

            if ($notifyUsers->isNotEmpty()) {
                Notification::send(
                    $notifyUsers,
                    new MeetingInvitationNotification($meeting->fresh(), NotificationType::MeetingUpdated),
                );
            }

            return $meeting->load(['creator', 'project', 'users', 'teams']);
        });
    }
}
