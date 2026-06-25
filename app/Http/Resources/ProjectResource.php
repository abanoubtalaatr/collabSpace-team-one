<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'deadline' => $this->deadline?->toDateString(),
            'priority' => $this->priority,
            'status' => $this->status,
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            /*'teams'       => $this->whenLoaded('teams', fn () =>
                $this->teams->map(fn ($team) => [
                    'id'   => $team->id,
                    'name' => $team->name,
                ])
            ),*/
            // 'tasks_count' => $this->whenLoaded('tasks', fn () => $this->tasks->count()),
            'media' => $this->whenLoaded('media', fn () => $this->getMedia('attachments')->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getFullUrl(),
            ])
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
