<?php

namespace App\DTOs;

final readonly class NotificationData
{
    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public int $actorId,
        public int $targetUserId,
        public string $entityType,
        public int $entityId,
        public ?string $oldStatus = null,
        public ?string $newStatus = null,
    ) {}

    /**
     * @return array{
     *     type: string,
     *     title: string,
     *     message: string,
     *     actor_id: int,
     *     target_user_id: int,
     *     entity_type: string,
     *     entity_id: int,
     *     old_status: string|null,
     *     new_status: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'actor_id' => $this->actorId,
            'target_user_id' => $this->targetUserId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}
