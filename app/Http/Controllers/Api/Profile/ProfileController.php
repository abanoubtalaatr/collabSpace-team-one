<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\Profile\ProfileResource;
use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function show(Request $request): ProfileResource
    {
        $user = $this->profileService->getProfile($request->user());

        return new ProfileResource($user);
    }

    public function update(UpdateProfileRequest $request): ProfileResource
    {
        $user = $request->user();
        $user->update($request->profileAttributes());

        $user = $this->profileService->getProfile($user->fresh());

        return new ProfileResource($user);
    }
}
