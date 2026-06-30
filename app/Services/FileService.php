<?php

namespace App\Services;

use App\Enums\FileStatus;
use App\Enums\FileType;
use App\Models\File;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileService
{
    public function __construct(private readonly ChatService $chatService) {}

    public function store(
        UploadedFile $uploadedFile,
        User $user,
        ?string $displayName = null,
        ?Model $attachable = null,
    ): File {
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'bin');
        $storedPath = $uploadedFile->store('files/'.now()->format('Y/m'), 'public');

        $file = File::create([
            'user_id' => $user->id,
            'name' => $displayName ?? $uploadedFile->getClientOriginalName(),
            'original_name' => $uploadedFile->getClientOriginalName(),
            'file_name' => $storedPath,
            'disk' => 'public',
            'mime_type' => $uploadedFile->getClientMimeType(),
            'extension' => $extension,
            'file_type' => FileType::fromExtension($extension),
            'size' => $uploadedFile->getSize(),
            'status' => FileStatus::Detached,
        ]);

        if ($attachable !== null) {
            $this->attach($file, $attachable, $user);
        }

        return $file->fresh(['uploader:id,name,email', 'attachable']);
    }

    public function attach(File $file, Model $attachable, User $user): File
    {
        $this->authorizeAttachable($attachable, $user);

        if ($file->isAttached() && $file->attachable_type !== null) {
            abort(422, 'File is already attached. Detach it first or upload a new file.');
        }

        $file->update([
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'status' => FileStatus::Attached,
        ]);

        return $file->fresh(['uploader:id,name,email', 'attachable']);
    }

    public function detach(File $file, User $user): File
    {
        $this->authorizeFile($file, $user);

        $file->update([
            'attachable_type' => null,
            'attachable_id' => null,
            'status' => FileStatus::Detached,
        ]);

        return $file->fresh(['uploader:id,name,email']);
    }

    public function delete(File $file, User $user): void
    {
        $this->authorizeFile($file, $user);

        if (Storage::disk($file->disk)->exists($file->file_name)) {
            Storage::disk($file->disk)->delete($file->file_name);
        }

        $file->delete();
    }

    public function authorizeFile(File $file, User $user): void
    {
        if ($user->hasRole('admin') || $file->user_id === $user->id) {
            return;
        }

        if ($file->isAttached() && $file->attachable instanceof Project) {
            if ($this->chatService->canAccessProject($user, $file->attachable)) {
                return;
            }
        }

        if ($file->isAttached() && $file->attachable instanceof Task) {
            $task = $file->attachable->loadMissing('project');
            if ($task->users()->where('users.id', $user->id)->exists()) {
                return;
            }
            if ($task->project && $this->chatService->canAccessProject($user, $task->project)) {
                return;
            }
        }

        abort(403, 'You are not allowed to manage this file.');
    }

    public function authorizeAttachable(Model $attachable, User $user): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        if ($attachable instanceof Project) {
            abort_unless(
                $this->chatService->canAccessProject($user, $attachable),
                403,
                'You do not have access to this project.'
            );

            return;
        }

        if ($attachable instanceof Task) {
            $attachable->loadMissing('project');
            $canAccess = $attachable->users()->where('users.id', $user->id)->exists()
                || ($attachable->project && $this->chatService->canAccessProject($user, $attachable->project));

            abort_unless($canAccess, 403, 'You do not have access to this task.');

            return;
        }

        abort(403, 'Invalid attachable type.');
    }

    public function resolveAttachable(string $type, int $id): Model
    {
        return match ($type) {
            'project' => Project::query()->findOrFail($id),
            'task' => Task::query()->findOrFail($id),
            default => abort(422, 'attachable_type must be project or task.'),
        };
    }
}
