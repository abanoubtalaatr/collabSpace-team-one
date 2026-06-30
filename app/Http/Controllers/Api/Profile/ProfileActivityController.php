<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Profile\ProfileActivityResource;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileActivityController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $activities = $this->profileService->getRecentActivity(
            $request->user(),
            $request->integer('limit', 15)
        );

        return ProfileActivityResource::collection($activities);
    }
}
