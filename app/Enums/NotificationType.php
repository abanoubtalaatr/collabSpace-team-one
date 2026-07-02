<?php

namespace App\Enums;

enum NotificationType: string
{
    case MeetingInvitation = 'meeting_invitation';
    case MeetingUpdated = 'meeting_updated';
    case MeetingCancelled = 'meeting_cancelled';
}
