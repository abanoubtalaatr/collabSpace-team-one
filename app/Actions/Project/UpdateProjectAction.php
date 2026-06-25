<?php

namespace App\Actions\Project;

use App\DTOs\ProjectDTO;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;

class UpdateProjectAction
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
    ) {}

    public function execute(Project $project, ProjectDTO $dto): Project
    {
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

        return $project;
    }
}
