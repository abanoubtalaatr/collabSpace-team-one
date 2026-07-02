<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\StoreFileRequest;
use App\Http\Resources\FileResource;
use App\Models\Task;
use App\Services\ChatService;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskFileController extends Controller
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly ChatService $chatService,
    ) {}

    public function index(Request $request, Task $task): AnonymousResourceCollection
    {
        $task->loadMissing('project');
        $canAccess = $task->users()->where('users.id', $request->user()->id)->exists()
            || ($task->project && $this->chatService->canAccessProject($request->user(), $task->project));

        abort_unless($canAccess, 403, 'You do not have access to this task.');
        $files = $task->files()
            ->with(['uploader:id,name,email'])
            ->where('status', 'attached')
            ->latest()
            ->get();

        return FileResource::collection($files);
    }

    public function store(StoreFileRequest $request, Task $task): FileResource
    {
        $file = $this->fileService->store(
            $request->file('file'),
            $request->user(),
            $request->input('name'),
            $task,
        );

        return new FileResource($file);
    }
}
