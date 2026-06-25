<?php

namespace App\Http\Controllers\Api;

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
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Project::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

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
            'success' => true,
            'data' => [
                'total_projects' => $totalProjects,
                'active_projects' => $activeProjects,
                'delayed_projects' => $delayedProjects,
                'completed_projects' => $completedProjects,
                'completion_rate' => round($completionRate, 2).'%',
            ],
        ]);
    }
}
