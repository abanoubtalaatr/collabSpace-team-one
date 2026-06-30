<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ProjectOverviewRequest;
use App\Http\Resources\Dashboard\ProjectOverviewResource;
use App\Http\Resources\Dashboard\RecentFileResource;
use App\Http\Resources\Dashboard\StatsResource;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service,
    ) {}

    public function stats(Request $request): StatsResource
    {
        return new StatsResource($this->service->stats($request->user()));
    }

    public function recentFiles(Request $request): AnonymousResourceCollection
    {
        return RecentFileResource::collection($this->service->recentFiles($request->user()));
    }

    public function projectOverview(ProjectOverviewRequest $request): AnonymousResourceCollection
    {
        return ProjectOverviewResource::collection(
            $this->service->projectOverview($request->user(), (int) $request->validated('project_id'))
        );
    }
}
