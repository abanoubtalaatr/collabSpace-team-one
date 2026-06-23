<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Traits\ApiResponse;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reports = Report::with('user')->latest()->get();

        return $this->apiResponse([
            'success' => true,
            'data' => ReportResource::collection($reports)
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validate the request
        $report = Report::create([
            'user_id' => auth()->id() ?? 1, // 1 as fallback for testing without auth
            'report_type' => $request->report_type,
            'note' => $request->note,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return $this->apiResponse([
            'success' => true,
            'message' => 'Report created successfully',
            'data' => new ReportResource($report)
        ], 201);
    }

    /**
     * Display project report statistics based on the given date range.
     */
    public function getProjectReport(Request $request)
    {
        //
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
                'completion_rate' => round($completionRate, 2) . '%',
            ]
        ]);
    }

    /**
     * Display task report statistics based on the given date range.
     */
    public function getTaskReport(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Task::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // calculate task statistics
        $totalTasks = $query->count();
        $completedTasks = (clone $query)->where('status', 'completed')->count();
        $pendingTasks = (clone $query)->where('status', 'pending')->count();

        // calculate productivity statistics
        $productivity = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        return $this->apiResponse([
            'success' => true,
            'report_type' => 'task',
            'data' => [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'pending_tasks' => $pendingTasks,
                'productivity_statistics' => round($productivity, 2) . '%',
            ]
        ], 200);
    }


    /**
     * Display team report statistics based on the given team ID.
     */
    public function getTeamReport(Request $request, $teamId)
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
                'active_tasks_count' => $user->tasks_count
            ];
        });

        return $this->apiResponse([
            'success' => true,
            'report_type' => 'team',
            'team_name' => $team->name,
            'data' => [
                'active_projects' => $activeProjects,
                'completion_rates' => round($completionRate, 2) . '%',
                'workload_distribution' => $workloadDistribution,
                'team_performance' => $completionRate > 75 ? 'High' : 'Normal',
            ]
        ], 200);
    }


    /**
     * Display user report statistics based on the given user ID.
     */
    public function getUserReport(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $assignedTasks = $user->tasks()->count();
        $completedTasks = $user->tasks()->where('status', 'completed')->count();

        // count uploaded files for the user
        $uploadedFilesCount = DB::table('media')->where('model_type', User::class)->where('model_id', $userId)->count();

        // calculate productivity score
        $productivityScore = $assignedTasks > 0 ? ($completedTasks / $assignedTasks) * 100 : 0;

        return $this->apiResponse([
            'success' => true,
            'report_type' => 'user',
            'data' => [
                'personal_information' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'assigned_tasks' => $assignedTasks,
                'completed_tasks' => $completedTasks,
                'uploaded_files' => $uploadedFilesCount,
                'meeting_attendance' => rand(80, 100) . '%', // Randomized for demonstration
                'productivity_score' => round($productivityScore, 2) . '%',
                'performance_overview' => $productivityScore >= 80 ? 'Excellent' : 'Good',
            ]
        ], 200);
    }
}
