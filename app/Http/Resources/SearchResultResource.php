<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        return [
            'type' => $this->resource->type,
            'id' => $this->resource->searchable->getKey(),
            'title' => $this->resource->title,
            'data' => $this->resource->searchable->toArray(),
        ];
    }
}
