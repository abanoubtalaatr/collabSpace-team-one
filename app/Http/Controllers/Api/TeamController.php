<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $completedProjects = $team->projects()->where('status', 'completed')->count();

        $completionRate = $totalProjects > 0 ? ($completedProjects / $totalProjects) * 100 : 0;

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

        return $this->apiResponse([
            'success' => true,
            'report_type' => 'team',
            'team_name' => $team->name,
            'data' => [
                'active_projects' => $activeProjects,
                'completion_rates' => round($completionRate, 2).'%',
                'workload_distribution' => $workloadDistribution,
                'team_performance' => $completionRate > 75 ? 'High' : 'Normal',
            ],
        ], 200);
    }
}
