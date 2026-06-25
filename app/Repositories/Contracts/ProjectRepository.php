<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Queries
    |--------------------------------------------------------------------------
    */

    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return Project::filter($request)
            ->with(['creator', 'media'])
            ->latest()
            ->paginate($perPage);
    }

    public function getByCreatorPaginated(Request $request, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Project::filter($request)
            ->with(['creator', 'media'])
            ->where('created_by', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getForTeamMemberPaginated(Request $request, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Project::filter($request)
            ->with(['creator', 'media'])
            ->whereHas('teams.members', fn ($q) => $q->where('users.id', $userId))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Project
    {
        return Project::with(['creator', 'media'])->find($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Mutations
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->refresh();
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
