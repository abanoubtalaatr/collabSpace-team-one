<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardOverviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => $this->resource['user'],
            'project' => $this->resource['project'],
            'stats' => $this->resource['stats'],
            'chart_data' => $this->resource['chart_data'],
            'recent_files' => RecentFileResource::collection($this->resource['recent_files']),
            'team_members' => $this->resource['team_members'],
        ];
    }
}
