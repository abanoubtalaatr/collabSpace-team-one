<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\StoreFileRequest;
use App\Http\Resources\FileResource;
use App\Models\Project;
use App\Services\ChatService;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectFileController extends Controller
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly ChatService $chatService,
    ) {}

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        abort_unless(
            $this->chatService->canAccessProject($request->user(), $project),
            403,
            'You do not have access to this project.'
        );

        $files = $project->files()
            ->with(['uploader:id,name,email'])
            ->where('status', 'attached')
            ->latest()
            ->get();

        return FileResource::collection($files);
    }

    public function store(StoreFileRequest $request, Project $project): FileResource
    {
        $file = $this->fileService->store(
            $request->file('file'),
            $request->user(),
            $request->input('name'),
            $project,
        );

        return new FileResource($file);
    }
}
