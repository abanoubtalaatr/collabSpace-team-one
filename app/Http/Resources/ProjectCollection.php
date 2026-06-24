<?php

namespace App\Http\Resources;

use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;


class ProjectCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'projects' => ProjectResource::collection($this->collection),
        ];
    }
}
