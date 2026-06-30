<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'month' => $this->resource['month'],
            'total_tasks' => (int) $this->resource['total_tasks'],
            'completed_tasks' => (int) $this->resource['completed_tasks'],
        ];
    }
}
