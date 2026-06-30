<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pending_tasks' => (int) $this->resource['pending_tasks'],
            'in_progress_tasks' => (int) $this->resource['in_progress_tasks'],
            'in_review_tasks' => (int) $this->resource['in_review_tasks'],
            'completed_tasks' => (int) $this->resource['completed_tasks'],
            'total_tasks' => (int) $this->resource['total_tasks'],
            'completion_rate' => $this->resource['completion_rate'],
        ];
    }
}
