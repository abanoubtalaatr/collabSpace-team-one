<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Display user report statistics based on the given user ID.
     */
    public function getUserReport(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        // calculate assigned tasks for the user
        $currentMonthTotalTasks = $user->tasks()->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // calculate completed tasks for the user in the current month
        $currentMonthCompletedTasks = $user->tasks()->where('status', 'completed')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // count uploaded files for the user
        $uploadedFilesCount = DB::table('media')->where('model_type', User::class)->where('model_id', $userId)->count();

        // calculate productivity score
        $productivityScore = $currentMonthTotalTasks > 0
            ? ($currentMonthCompletedTasks / $currentMonthTotalTasks) * 100
            : 0;

        /**
         * Generate a chart showing the number of tasks completed by the user over the last 6 months.
         */

        $chartLabels = [];
        $chartData = [];

        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        // Group tasks by month for the last 6 months
        $userTasksGrouped = $user->tasks()->where('user_id', $userId)
            ->where('status', 'completed')
            ->where('updated_at', '>=', $sixMonthsAgo)
            ->get()
            ->groupBy(function ($task) {
                return $task->updated_at->format('Y-m');
            });

        // Generate chart data for the last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $chartLabels[] = $month->format('M');

            $key = $month->format('Y-m');

            $chartData[] = $userTasksGrouped->has($key)
                ? $userTasksGrouped->get($key)->count()
                : 0;
        }

        return $this->apiResponse([
            'report_type' => 'user',
            'personal_information' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'assigned_tasks' => $currentMonthTotalTasks,
            'completed_tasks' => $currentMonthCompletedTasks,
            'uploaded_files' => $uploadedFilesCount,
            'meeting_attendance' => rand(80, 100).'%', // Randomized for demonstration
            'productivity_score' => round($productivityScore, 2).'%',
            'performance_overview' => $productivityScore >= 80 ? 'Excellent' : 'Good',

            // Generate a chart showing the number of tasks completed by the user over the last 6 months.
            'worker_activity_chart' => [
                'labels' => $chartLabels,
                'data'   => $chartData
            ]
        ], 'User report generated successfully');
    }
}
