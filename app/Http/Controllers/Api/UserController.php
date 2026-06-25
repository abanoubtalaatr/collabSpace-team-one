<?php

namespace App\Http\Controllers\Api;

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
                'meeting_attendance' => rand(80, 100).'%', // Randomized for demonstration
                'productivity_score' => round($productivityScore, 2).'%',
                'performance_overview' => $productivityScore >= 80 ? 'Excellent' : 'Good',
            ],
        ], 200);
    }
}
