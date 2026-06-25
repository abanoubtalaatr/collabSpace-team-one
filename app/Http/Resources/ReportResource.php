<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        // tructure the report resource data
        return [
            'id' => $this->id,
            'report_type' => $this->report_type,
            'note' => $this->note,
            'period' => [
                'start' => $this->start_date,
                'end' => $this->end_date,
            ],
            'created_by' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
