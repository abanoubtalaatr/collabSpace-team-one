<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStoreRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $reports = Report::with('user')->latest()->get();

        return $this->apiResponse([
            ReportResource::collection($reports),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReportStoreRequest $request): JsonResponse
    {
        $report = Report::create([
            'user_id' => $request->user()->id,
            ...$request->safe()->only(['report_type', 'note', 'start_date', 'end_date']),
        ]);

        return $this->apiResponse(
            new ReportResource($report->load('user')),
            'Report created successfully',
            201,
        );
    }
}
