<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    use ApiResponse;

    /**
     * Display task report statistics based on the given date range.
     */
    public function getTaskReport(Request $request): JsonResponse
    {
        $dates = array_filter($request->only(['start_date', 'end_date']));

        $query = Task::query()
        ->when(count($dates) === 2, function ($q) use ($dates){
            $q->whereBetween('created_at', [$dates['start_date'], $dates['end_date']]);
        });

        // calculate total tasks
        $totalTasks = $query->count();
        $pendingTasks = (clone $query)->where('status', 'Pending')->count();

        // calculate current month statistics
        $currentMonthTotalTasks = Task::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // calculate current month completed tasks
        $currentMonthCompletedTasks = Task::where('status', 'completed')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // calculate completion rate
        $completionRate = $currentMonthTotalTasks > 0
            ? ($currentMonthCompletedTasks / $currentMonthTotalTasks) * 100
            : 0;

        /**
         * Calculate growth rate for tasks completed in the current month compared to last month.
         */
        // calculate last month completed tasks
        $lastMonthCompletedTasks = Task::where('status', 'completed')
        ->whereMonth('updated_at', now()->subMonth()->month)
        ->whereYear('updated_at', now()->subMonth()->year)
        ->count();

        // calculate growth rate
        if ($lastMonthCompletedTasks > 0) {
            $growthRate = (($currentMonthCompletedTasks - $lastMonthCompletedTasks) / $lastMonthCompletedTasks) * 100;
            $growthRate = round($growthRate, 1);
        } else {
            $growthRate = $currentMonthCompletedTasks > 0 ? 100 : 0;
        }

        // Prepare the growth rate message
        $comparisonText = $growthRate >= 0
            ? "+{$growthRate}% from last month"
            : "{$growthRate}% from last month";

        /**
         * Generate a chart showing the number of tasks completed over the last 6 months.
         */
        // Prepare chart data
        $chartLabels = [];
        $chartData = [];

        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        // Group completed tasks by month for the last 6 months
        $completedTasksGrouped = Task::where('status', 'completed')
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

            // If there are completed tasks for this month, get the count; otherwise, set it to 0
            $chartData[] = $completedTasksGrouped->has($key)
                ? $completedTasksGrouped->get($key)->count()
                : 0;
        }

        return $this->apiResponse([
            'report_type'             => 'task',
            'total_tasks'             => $totalTasks,
            'pending_tasks'           => $pendingTasks,
            'currentMonthTotalTasks'  => $currentMonthTotalTasks,
            'task_completion_rate' => [
                'completionRate' => round($completionRate, 2).'%',
                'comparison' => $comparisonText
            ],
            'task_completion_chart' => [
                'labels' => $chartLabels,
                'data' => $chartData
            ]
        ], 'Task report generated successfully');
    }
}
