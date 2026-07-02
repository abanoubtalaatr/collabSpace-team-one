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

            if (isset($data['user_ids']) || isset($data['team_ids'])) {
                $userIds = $data['user_ids'] ?? $meeting->users()->pluck('users.id')->all();
                $teamIds = $data['team_ids'] ?? $meeting->teams()->pluck('teams.id')->all();

                $participants = $this->resolveParticipants->execute($userIds, $teamIds, $meeting->created_by);

                $meeting->users()->sync($participants->pluck('id'));
                $meeting->teams()->sync($teamIds);
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
