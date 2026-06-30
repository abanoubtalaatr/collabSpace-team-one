<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->when(count($dates) === 2, function ($q) use ($dates) {
                $q->whereBetween('created_at', [$dates['start_date'], $dates['end_date']]);
            });

        $totalTasks = $query->count();

        $completedTasks = (clone $query)->where('status', 'completed')->count();
        $pendingTasks = (clone $query)->where('status', 'Pending')->count();

        $productivity = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        return $this->apiResponse([
            'report_type' => 'task',
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $pendingTasks,
            'productivity_statistics' => round($productivity, 2).'%',
        ], 'Task report generated successfully');
    }
}
