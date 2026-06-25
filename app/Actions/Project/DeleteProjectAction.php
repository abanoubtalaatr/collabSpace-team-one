<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;

class DeleteProjectAction
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
    ) {}

    public function execute(Project $project): void
    {
        $project->clearMediaCollection(Project::MEDIA_COLLECTION_ATTACHMENTS);

        $this->repository->delete($project);
    }
}
