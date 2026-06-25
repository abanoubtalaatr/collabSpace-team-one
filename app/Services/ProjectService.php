<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Queries
    |--------------------------------------------------------------------------
    */

    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getAllPaginated($request, $perPage);
    }

    public function getByCreatorPaginated(Request $request, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByCreatorPaginated($request, $userId, $perPage);
    }

    public function getForTeamMemberPaginated(Request $request, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getForTeamMemberPaginated($request, $userId, $perPage);
    }

    public function findOrFail(int $id): Project
    {
        $project = $this->repository->findById($id);

        abort_unless($project !== null, 404, 'Project not found.');

        return $project;
    }
}
