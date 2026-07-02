<?php

namespace App\Http\Controllers\Api\Profile;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Profile\ProfileTaskSummaryResource;
use App\Http\Resources\TaskResource;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProfileTaskController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['sometimes', Rule::in(TaskStatus::values())],
            'classification' => ['sometimes', Rule::in(['to_do', 'done', 'in_progress', 'in_review', 'completed', 'pending'])],
        ]);

        $query = $request->user()
            ->tasks()
            ->with(['project:id,name', 'users:id,name,email', 'users.media']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('classification')) {
            $status = match ($request->string('classification')->value()) {
                'to_do', 'pending' => TaskStatus::Pending->value,
                'done', 'completed' => TaskStatus::Completed->value,
                'in_progress' => TaskStatus::InProgress->value,
                'in_review' => TaskStatus::InReview->value,
                default => null,
            };

            if ($status) {
                $query->where('status', $status);
            }
        }

        $tasks = $query
            ->latest('updated_at')
            ->paginate($request->integer('per_page', 15));

        return TaskResource::collection($tasks);
    }

    public function summary(Request $request): ProfileTaskSummaryResource
    {
        $summary = $this->profileService->getTaskSummary($request->user());

        return new ProfileTaskSummaryResource($summary);
    }
}
