<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileTaskSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'to_do' => $this->resource['to_do'],
            'in_progress' => $this->resource['in_progress'],
            'in_review' => $this->resource['in_review'],
            'completed' => $this->resource['completed'],
            'done' => $this->resource['done'],
            'total' => $this->resource['total'],
            'by_status' => [
                'pending' => $this->resource['pending'],
                'in_progress' => $this->resource['in_progress'],
                'in_review' => $this->resource['in_review'],
                'completed' => $this->resource['completed'],
            ],
        ];
    }
}
