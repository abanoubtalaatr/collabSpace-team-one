<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecentFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $project = $this->model;
        $uploader = $project?->creator;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->getFullUrl(),
            'project_name' => $project?->name,
            'uploaded_by' => $uploader?->name,
            'uploaded_by_id' => $uploader?->id,
            'avatar_url' => $uploader?->avatarUrl(),
            'created_at' => $this->created_at?->format('H:i'),
        ];
    }
}
