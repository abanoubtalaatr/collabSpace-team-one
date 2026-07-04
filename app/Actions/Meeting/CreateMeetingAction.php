<?php

namespace App\Actions\Meeting;

use App\Enums\NotificationType;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CreateMeetingAction
{
    public function __construct(
        private readonly ResolveMeetingParticipantsAction $resolveParticipants,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $creator): Meeting
    {
        return DB::transaction(function () use ($data, $creator) {
            $meeting = Meeting::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'meeting_link' => $data['meeting_link'] ?? null,
                'location' => $data['location'] ?? null,
                'created_by' => $creator->id,
                'project_id' => $data['project_id'] ?? null,
            ]);

            $userIds = $data['user_ids'] ?? [];
            $teamIds = $data['team_ids'] ?? [];

            $participants = $this->resolveParticipants->execute($userIds, $teamIds, $creator->id);

            $meeting->users()->sync($participants->pluck('id'));

            if ($teamIds !== []) {
                $meeting->teams()->sync($teamIds);
            }

            $invitees = $participants->where('id', '!=', $creator->id);

            if ($invitees->isNotEmpty()) {
                Notification::send(
                    $invitees,
                    new MeetingInvitationNotification($meeting, NotificationType::MeetingInvitation),
                );
            }

            return $meeting->load(['creator', 'project', 'users', 'teams']);
        });
    }
}
