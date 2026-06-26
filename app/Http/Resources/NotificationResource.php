<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'data' => $data,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'actor_id' => $data['actor_id'] ?? null,
            'target_user_id' => $data['target_user_id'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'old_status' => $data['old_status'] ?? null,
            'new_status' => $data['new_status'] ?? null,
            'read_at' => $this->read_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
