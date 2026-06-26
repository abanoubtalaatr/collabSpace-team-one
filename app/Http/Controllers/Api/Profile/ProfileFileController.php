<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UploadProfileFileRequest;
use App\Http\Resources\Profile\ProfileFileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProfileFileController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $files = $request->user()->getMedia(User::MEDIA_COLLECTION_FILES);

        return ProfileFileResource::collection($files);
    }

    public function store(UploadProfileFileRequest $request): ProfileFileResource
    {
        $media = $request->user()
            ->addMediaFromRequest('file')
            ->toMediaCollection(User::MEDIA_COLLECTION_FILES);

        return new ProfileFileResource($media);
    }

    public function destroy(Request $request, int $fileId): JsonResponse
    {
        $media = Media::query()->findOrFail($fileId);

        abort_unless(
            $media->model_type === $request->user()->getMorphClass()
                && (int) $media->model_id === $request->user()->id
                && $media->collection_name === User::MEDIA_COLLECTION_FILES,
            403,
            'You cannot delete this file.'
        );

        $media->delete();

        return response()->json(['message' => 'File deleted successfully.']);
    }
}
