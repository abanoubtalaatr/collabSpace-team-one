<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    public function getByCreatorPaginated(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function getForTeamMemberPaginated(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Project;

    public function create(array $data): Project;

    public function update(Project $project, array $data): Project;

    public function delete(Project $project): void;

    public function syncTeams(Project $project, array $teamIds): void;
}