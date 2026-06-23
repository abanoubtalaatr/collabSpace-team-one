<?php

namespace App\Services;

use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\DeleteProjectAction;
use App\Actions\Project\UpdateProjectAction;
use App\DTOs\ProjectDTO;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
        private readonly CreateProjectAction        $createAction,
        private readonly UpdateProjectAction        $updateAction,
        private readonly DeleteProjectAction        $deleteAction,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Queries
    |--------------------------------------------------------------------------
    */

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getAllPaginated($perPage);
    }

    public function getByCreatorPaginated(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByCreatorPaginated($userId, $perPage);
    }

    public function getForTeamMemberPaginated(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getForTeamMemberPaginated($userId, $perPage);
    }

    public function findOrFail(int $id): Project
    {
        $project = $this->repository->findById($id);

        abort_unless($project !== null, 404, 'Project not found.');

        return $project;
    }

    /*
    |--------------------------------------------------------------------------
    | Mutations
    |--------------------------------------------------------------------------
    */

    public function create(ProjectDTO $dto): Project
    {
        return $this->createAction->execute($dto);
    }

    public function update(Project $project, ProjectDTO $dto): Project
    {
        return $this->updateAction->execute($project, $dto);
    }

    public function delete(Project $project): void
    {
        $this->deleteAction->execute($project);
    }
}