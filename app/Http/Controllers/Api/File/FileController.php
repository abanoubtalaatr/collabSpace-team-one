<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\AttachFileRequest;
use App\Http\Requests\File\ListFilesRequest;
use App\Http\Requests\File\StoreFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\Project;
use App\Models\Task;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FileController extends Controller
{
    public function __construct(private readonly FileService $fileService) {}

    public function index(ListFilesRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = File::query()
            ->with(['uploader:id,name,email'])
            ->when($request->boolean('mine'), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('file_type'), fn ($q) => $q->where('file_type', $request->string('file_type')))
            ->when($request->filled('extension'), fn ($q) => $q->where('extension', $request->string('extension')))
            ->when($request->filled('project_id'), function ($q) use ($request) {
                $q->where('attachable_type', 'project')
                    ->where('attachable_id', $request->integer('project_id'))
                    ->where('status', 'attached');
            })
            ->when($request->filled('task_id'), function ($q) use ($request) {
                $q->where('attachable_type', 'task')
                    ->where('attachable_id', $request->integer('task_id'))
                    ->where('status', 'attached');
            })
            ->latest();

        $files = $query->paginate($request->integer('per_page', 15));

        return FileResource::collection($files);
    }

    public function store(StoreFileRequest $request): FileResource
    {
        $attachable = null;

        if ($request->filled('project_id')) {
            $attachable = Project::query()->findOrFail($request->integer('project_id'));
        } elseif ($request->filled('task_id')) {
            $attachable = Task::query()->findOrFail($request->integer('task_id'));
        }

        $file = $this->fileService->store(
            $request->file('file'),
            $request->user(),
            $request->input('name'),
            $attachable,
        );

        return new FileResource($file);
    }

    public function show(Request $request, File $file): FileResource
    {
        $this->fileService->authorizeFile($file, $request->user());

        $file->load(['uploader:id,name,email', 'attachable']);

        return new FileResource($file);
    }

    public function attach(AttachFileRequest $request, File $file): FileResource
    {
        $attachable = $this->fileService->resolveAttachable(
            $request->string('attachable_type')->value(),
            $request->integer('attachable_id'),
        );

        $file = $this->fileService->attach($file, $attachable, $request->user());
        $file->load(['uploader:id,name,email', 'attachable']);

        return new FileResource($file);
    }

    public function detach(Request $request, File $file): FileResource
    {
        $file = $this->fileService->detach($file, $request->user());

        return new FileResource($file);
    }

    public function destroy(Request $request, File $file): JsonResponse
    {
        $this->fileService->delete($file, $request->user());

        return response()->json(['message' => 'File deleted successfully.']);
    }
}
