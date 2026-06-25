<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ApiResponse;

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
                'productivity_statistics' => round($productivity, 2).'%',
            ],
        ], 200);
    }
}
