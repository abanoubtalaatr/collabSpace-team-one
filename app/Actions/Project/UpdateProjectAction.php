<?php

namespace App\Actions\Project;

use App\DTOs\ProjectDTO;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\NotificationService;
use BackedEnum;

class UpdateProjectAction
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
        private readonly NotificationService $notifications,
    ) {}

    public function execute(Project $project, ProjectDTO $dto, ?User $actor = null): Project
    {
        $oldStatus = $this->statusValue($project->status);

        $project = $this->repository->update($project, [
            'name' => $dto->name,
            'description' => $dto->description,
            'start_date' => $dto->startDate,
            'deadline' => $dto->deadline,
            'priority' => $dto->priority,
            'status' => $dto->status,
        ]);

        // $this->repository->syncTeams($project, $dto->teamIds);

        if (! empty($dto->mediaFiles)) {
            foreach ($dto->mediaFiles as $file) {
                $project->addMedia($file)
                    ->toMediaCollection(Project::MEDIA_COLLECTION_ATTACHMENTS);
            }
        }

        $newStatus = $this->statusValue($project->status);

        if ($actor !== null && $oldStatus !== $newStatus) {
            $this->notifications->notifyProjectStatusUpdated($actor, $project->loadMissing(['creator']), $oldStatus, $newStatus);
        }

        return $project;
    }

    private function statusValue(mixed $status): string
    {
        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        return (string) $status;
    }
}
