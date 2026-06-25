<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStoreRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Traits\ApiResponse;

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
            'data' => ReportResource::collection($reports),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReportStoreRequest $request)
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
            'data' => new ReportResource($report),
        ], 201);
    }
}
