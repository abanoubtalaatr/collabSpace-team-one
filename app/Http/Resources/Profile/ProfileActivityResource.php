<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource['type'],
            'action' => $this->resource['action'],
            'title' => $this->resource['title'],
            'description' => $this->resource['description'],
            'status' => $this->resource['status'],
            'occurred_at' => $this->resource['occurred_at'],
            'meta' => $this->resource['meta'],
        ];
    }
}
