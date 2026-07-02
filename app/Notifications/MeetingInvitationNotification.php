<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Meeting $meeting,
        public readonly NotificationType $type = NotificationType::MeetingInvitation,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->type) {
            NotificationType::MeetingUpdated => 'Meeting updated: '.$this->meeting->title,
            NotificationType::MeetingCancelled => 'Meeting cancelled: '.$this->meeting->title,
            default => 'Meeting invitation: '.$this->meeting->title,
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line($this->messageText())
            ->line('Date: '.$this->meeting->starts_at->format('Y-m-d'))
            ->line('Time: '.$this->meeting->starts_at->format('H:i').' - '.$this->meeting->ends_at->format('H:i'))
            ->when($this->meeting->location, fn (MailMessage $mail) => $mail->line('Location: '.$this->meeting->location))
            ->when($this->meeting->meeting_link, fn (MailMessage $mail) => $mail->action('Join meeting', $this->meeting->meeting_link));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'meeting_id' => $this->meeting->id,
            'title' => $this->meeting->title,
            'starts_at' => $this->meeting->starts_at->toDateTimeString(),
            'ends_at' => $this->meeting->ends_at->toDateTimeString(),
            'location' => $this->meeting->location,
            'meeting_link' => $this->meeting->meeting_link,
            'message' => $this->messageText(),
        ];
    }

    private function messageText(): string
    {
        return match ($this->type) {
            NotificationType::MeetingUpdated => 'A meeting you are assigned to has been updated.',
            NotificationType::MeetingCancelled => 'A meeting you were assigned to has been cancelled.',
            default => 'You have been invited to a new meeting.',
        };
    }
}
