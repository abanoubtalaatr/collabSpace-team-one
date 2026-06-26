<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'file_type' => $this->file_type,
            'size' => $this->size,
            'status' => $this->status,
            'url' => $this->getUrl(),
            'is_attached' => $this->isAttached(),
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'attachable' => $this->whenLoaded('attachable', function () {
                if ($this->attachable === null) {
                    return null;
                }

                return [
                    'id' => $this->attachable->id,
                    'type' => $this->attachable_type,
                    'label' => $this->attachable->name ?? $this->attachable->title ?? null,
                ];
            }),
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
                'email' => $this->uploader->email,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
