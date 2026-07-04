<?php

namespace App\Actions\Meeting;

use App\Enums\NotificationType;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingInvitationNotification;
use Illuminate\Support\Facades\Notification;

class DeleteMeetingAction
{
    public function execute(Meeting $meeting, User $actor): void
    {
        $notifyUsers = $meeting->users()->where('users.id', '!=', $actor->id)->get();

        if ($notifyUsers->isNotEmpty()) {
            Notification::send(
                $notifyUsers,
                new MeetingInvitationNotification($meeting, NotificationType::MeetingCancelled),
            );
        }

        $meeting->delete();
    }
}
