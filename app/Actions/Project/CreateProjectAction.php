<?php

namespace App\Actions\Project;

use App\DTOs\ProjectDTO;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;

class CreateProjectAction
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
    ) {}

    public function execute(ProjectDTO $dto): Project
    {
        $project = $this->repository->create([
            'name'        => $dto->name,
            'description' => $dto->description,
            'start_date'  => $dto->startDate,
            'deadline'    => $dto->deadline,
            'priority'    => $dto->priority,
            'status'      => $dto->status,
            'created_by'  => $dto->createdBy,
        ]);

       /* if (!empty($dto->teamIds)) {
            $this->repository->syncTeams($project, $dto->teamIds);
        }*/

        foreach ($dto->mediaFiles as $file) {
            $project->addMedia($file)
                    ->toMediaCollection(Project::MEDIA_COLLECTION_ATTACHMENTS);
        }

        return $project->load(['creator',  'media']);//'tasks', 'teams',
    }
}