<?php

namespace App\Http\Resources\Dashboard;

use App\Models\File;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RecentFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof File) {
            return $this->fromFileModel();
        }

        if ($this->resource instanceof Media) {
            return $this->fromMediaModel();
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function fromFileModel(): array
    {
        /** @var File $file */
        $file = $this->resource;
        $uploader = $file->uploader;
        $project = $this->resolveProjectFromFile($file);

        return [
            'id' => $file->id,
            'name' => $file->name ?? $file->original_name,
            'url' => $file->getUrl(),
            'download_url' => route('files.download', $file->id),
            'project_name' => $project?->name,
            'uploaded_by' => $uploader?->name,
            'uploaded_by_id' => $uploader?->id,
            'avatar_url' => $uploader?->avatarUrl(),
            'created_at' => $file->created_at?->format('H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fromMediaModel(): array
    {
        /** @var Media $media */
        $media = $this->resource;

        if ($media->model instanceof User) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getFullUrl(),
                'download_url' => $media->getFullUrl(),
                'project_name' => null,
                'uploaded_by' => $media->model->name,
                'uploaded_by_id' => $media->model->id,
                'avatar_url' => $media->model->avatarUrl(),
                'created_at' => $media->created_at?->format('H:i'),
            ];
        }

        $project = $media->model instanceof Project ? $media->model : null;
        $uploader = $project?->creator;

        return [
            'id' => $media->id,
            'name' => $media->name,
            'url' => $media->getFullUrl(),
            'download_url' => $media->getFullUrl(),
            'project_name' => $project?->name,
            'uploaded_by' => $uploader?->name,
            'uploaded_by_id' => $uploader?->id,
            'avatar_url' => $uploader?->avatarUrl(),
            'created_at' => $media->created_at?->format('H:i'),
        ];
    }

    private function resolveProjectFromFile(File $file): ?Project
    {
        if ($file->attachable_type === 'project' && $file->attachable instanceof Project) {
            return $file->attachable;
        }

        if ($file->attachable_type === 'task' && $file->attachable instanceof Task) {
            return $file->attachable->project;
        }

        return null;
    }
}
