<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecentFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $project = $this->model;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'project_name' => $project?->name,
            'uploaded_by' => $project?->creator?->name,
            'created_at' => $this->created_at?->format('H:i'),
        ];
    }
}
