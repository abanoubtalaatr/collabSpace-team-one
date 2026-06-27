<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use ApiResponse;

    /**
     * Display project report statistics based on the given date range.
     */
    public function getProjectReport(Request $request)
    {
        $dates = array_filter($request->only(['start_date', 'end_date']));

        $query = Project::query()
        ->when(count($dates) === 2, function ($q) use ($dates){
            $q->whereBetween('created_at', [$dates['start_date'], $dates['end_date']]);
        });

        $totalProjects = $query->count();
        $activeProjects = (clone $query)->where('status', 'in_progress')->count();
        $completedProjects = (clone $query)->where('status', 'completed')->count();

        // calculate delayed projects
        $delayedProjects = Project::where('status', '!=', 'completed')
            ->where('deadline', '<', now())
            ->count();

        // calculate completion rate
        $completionRate = $totalProjects > 0 ? ($completedProjects / $totalProjects) * 100 : 0;

        return $this->apiResponse([
            'total_projects'     => $totalProjects,
            'active_projects'    => $activeProjects,
            'delayed_projects'   => $delayedProjects,
            'completed_projects' => $completedProjects,
            'completion_rate'    => round($completionRate, 2) . '%',
        ]);
    }
}
