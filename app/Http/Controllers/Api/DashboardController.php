<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardOverviewRequest;
use App\Http\Requests\Dashboard\ProjectOverviewRequest;
use App\Http\Resources\Dashboard\DashboardOverviewResource;
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

    public function overview(DashboardOverviewRequest $request): DashboardOverviewResource
    {
        $projectId = $request->filled('project_id')
            ? $request->integer('project_id')
            : null;

        return new DashboardOverviewResource(
            $this->service->overview($request->user(), $projectId)
        );
    }

    public function stats(Request $request): StatsResource
    {
        return new StatsResource($this->service->stats($request->user()));
    }

    public function recentFiles(Request $request): AnonymousResourceCollection
    {
        return RecentFileResource::collection($this->service->recentFiles($request->user()));
    }

    public function projectOverview(ProjectOverviewRequest $request): DashboardOverviewResource
    {
        return new DashboardOverviewResource(
            $this->service->projectOverview($request->user(), $request->integer('project_id'))
        );
    }
}
