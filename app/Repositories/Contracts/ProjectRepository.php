<?php

namespace App\Repositories;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Project::with(['creator', 'media'])//'teams'
            ->latest()
            ->paginate($perPage);
    }

    public function getByCreatorPaginated(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Project::with(['creator', 'teams', 'media'])
            ->createdBy($userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getForTeamMemberPaginated(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Project::with(['creator', 'teams', 'media'])
            ->forTeamMember($userId)
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Project
    {
        return Project::with(['creator',  'media'])->find($id);//'tasks', 'teams'
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh(['creator', 'media']);//'tasks', 'teams'
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function syncTeams(Project $project, array $teamIds): void
    {
        $project->teams()->sync($teamIds);
    }
}