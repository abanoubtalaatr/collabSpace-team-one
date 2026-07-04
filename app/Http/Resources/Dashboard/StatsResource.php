<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id' => (int) $this->resource['user']['id'],
                'name' => $this->resource['user']['name'],
                'email' => $this->resource['user']['email'],
                'avatar_url' => $this->resource['user']['avatar_url'],
            ],
            'pending_tasks' => (int) $this->resource['pending_tasks'],
            'in_progress_tasks' => (int) $this->resource['in_progress_tasks'],
            'in_review_tasks' => (int) $this->resource['in_review_tasks'],
            'completed_tasks' => (int) $this->resource['completed_tasks'],
            'total_tasks' => (int) $this->resource['total_tasks'],
            'progress' => $this->resource['progress'],
            'completion_rate' => $this->resource['completion_rate'],
            'chart_data' => [
                'status' => $this->resource['chart_data']['status'],
                'monthly' => $this->resource['chart_data']['monthly'],
            ],
        ];
    }
}
