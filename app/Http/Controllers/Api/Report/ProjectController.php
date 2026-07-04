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

        $totalProjects      = $query->count();
        $activeProjects     = (clone $query)->where('status', 'in_progress')->count();

        // calculate delayed projects
        $delayedProjects = Project::where('status', '!=', 'completed')
            ->where('deadline', '<', now())
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // calculate current month statistics
        $currentMonthTotal = Project::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // calculate current month completed projects
        $currentMonthCompleted = Project::where('status', 'completed')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // calculate completion rate
        $completionRate = $currentMonthTotal > 0
            ? ($currentMonthCompleted / $currentMonthTotal) * 100
            : 0;

        /**
         * Calculate growth rate compared to last month
         */
        // calculate last month completed projects
        $lastMonthCompleted = Project::where('status', 'completed')
            ->whereMonth('updated_at', now()->subMonth()->month)
            ->whereYear('updated_at', now()->subMonth()->year)
            ->count();

        // calculate growth rate
        if ($lastMonthCompleted > 0) {
            $growthRate = (($currentMonthCompleted - $lastMonthCompleted) / $lastMonthCompleted) * 100;
            $growthRate = round($growthRate, 1);
        } else {
            $growthRate = $currentMonthCompleted > 0 ? 100 : 0;
        }

        $comparisonText = $growthRate >= 0
            ? "+{$growthRate}% from last month"
            : "{$growthRate}% from last month";

        /**
         * Prepare data for the projects progress chart
         */
        // Prepare chart data
        $chartLabels = [];
        $chartData = [];

        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        // Group completed projects by month for the last 6 months
        $completedProjectsGrouped = Project::where('status', 'completed')
            ->where('updated_at', '>=', $sixMonthsAgo)
            ->get()
            ->groupBy(function ($project) {
                return $project->updated_at->format('Y-m');
            });

        // Generate data for the last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $chartLabels[] = $month->format('M');

            $key = $month->format('Y-m');

            $chartData[] = $completedProjectsGrouped->has($key)
                ? $completedProjectsGrouped->get($key)->count()
                : 0;
        }

        return $this->apiResponse([
            'total_projects'     => $totalProjects,
            'active_projects'    => $activeProjects,
            'delayed_projects'   => $delayedProjects,
            'projects_completion_rate' => [
                'completionRate' => round($completionRate, 2) . '%',
                'comparison'     => $comparisonText
            ],
            'projects_progress_chart' => [
                'labels' => $chartLabels,
                'data'   => $chartData
            ]
        ],'Project report generated successfully');
    }
}
