<?php

namespace App\Notifications;

use App\DTOs\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CollaborationDatabaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly NotificationData $data,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->data->type;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->data->toArray();
    }
}
