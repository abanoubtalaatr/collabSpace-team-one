<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Team;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    use ApiResponse;

    /**
     * Display team report statistics based on the given team ID.
     */
    public function getTeamReport(Request $request, int $teamId)
    {
        $team = Team::findOrFail($teamId);

        // Get project statistics for the team
        $activeProjects = $team->projects()->where('status', 'in_progress')->count();
        $totalProjects = $team->projects()->count();

        // calculate current month statistics
        $currentMonthTotla = $team->projects()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // calculate current month completed projects
        $currentMonthCompleted = $team->projects()->where('status', 'completed')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $completionRate = $currentMonthTotla > 0
            ? ($currentMonthCompleted / $currentMonthTotla) * 100
            : 0;

        // Get workload distribution for team members
        $workloadDistribution = $team->users()->withCount(['tasks' => function ($query) {
            $query->where('status', 'in_progress');
        }])->get(['users.id', 'users.name'])->map(function ($user) {
            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'active_tasks_count' => $user->tasks_count,
            ];
        });

        /*
        * Calculate growth rate for team tasks completed in the current month compared to the last month.
        */

        // Get team tasks completed in the current month and last month
        $teamUserIds = $team->users()->pluck('users.id')->toArray();

        // Get team tasks completed in the current month
        $currentMonthTeamTasks = Task::whereIn('user_id', $teamUserIds)
            ->where('status', 'completed')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // Get team tasks completed in the last month
        $lastMonthTeamTasks = Task::whereIn('user_id', $teamUserIds)
            ->where('status', 'completed')
            ->whereMonth('updated_at', now()->subMonth()->month)
            ->whereYear('updated_at', now()->subMonth()->year)
            ->count();

        // calculate growth rate
        if ($lastMonthTeamTasks > 0) {
            $growthRate = (($currentMonthTeamTasks - $lastMonthTeamTasks) / $lastMonthTeamTasks) * 100;
            $growthRate = round($growthRate, 1);
        } else {
            $growthRate = $currentMonthTeamTasks > 0 ? 100 : 0;
        }

        $comparisonText = $growthRate >= 0
            ? "+{$growthRate}% from last month"
            : "{$growthRate}% from last month";


        /*
        * Prepare chart data for team tasks completed in the last 6 months.
        */
        $chartLabels = [];
        $chartData = [];
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        // Group completed tasks for the team by month for the last 6 months
        $teamTasksGrouped = Task::whereIn('user_id', $teamUserIds)
            ->where('status', 'completed')
            ->where('updated_at', '>=', $sixMonthsAgo)
            ->get()
            ->groupBy(function ($task) {
                return $task->updated_at->format('Y-m');
            });

        // Generate data for the last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $chartLabels[] = $month->format('M');

            $key = $month->format('Y-m');

            $chartData[] = $teamTasksGrouped->has($key)
                ? $teamTasksGrouped->get($key)->count()
                : 0;
        }

        return $this->apiResponse([
            'report_type' => 'team',
            'team_name' => $team->name,
            'active_projects' => $activeProjects,
            'workload_distribution' => $workloadDistribution,
            'team_performance' => $completionRate > 75 ? 'High' : 'Normal',
            'team_activity_rate'    => [
                'completionRate' => round($completionRate, 2).'%',
                'comparison'     => $comparisonText
            ],
            'worker_activity_chart' => [
                'labels' => $chartLabels,
                'data'   => $chartData
            ]
        ],'Team report generated successfully');
    }
}
