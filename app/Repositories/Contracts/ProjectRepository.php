<?php
namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
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
            ->with(['creator', 'media', 'guests', 'teams:id,name,display_name'])
            ->latest()
            ->paginate($perPage);
    }

    public function getByCreatorPaginated(Request $request, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Project::filter($request)
            ->with(['creator', 'media', 'guests', 'teams:id,name,display_name'])
            ->where('created_by', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getForTeamMemberPaginated(Request $request, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Project::filter($request)
            ->with(['creator', 'media', 'guests', 'teams:id,name,display_name'])
            ->whereHas('teams.members', fn($q) => $q->where('users.id', $userId))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Project
    {
        return Project::with(['creator', 'media', 'guests', 'teams:id,name,display_name'])->find($id);
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

    public function getProjectGuests()
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'member');
        })
            ->select([
                'id',
                'name',
                'email',
                'job_title',
                
            ])
            ->orderBy('name')
            ->get();
    }

    public function getMonthlyTasks(Project $project): array
    {
        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->select(['start_date', 'due_date'])
            ->get();

        $months = [];

        $current = Carbon::parse($project->start_date)->startOfMonth();
        $last = Carbon::parse($project->deadline)->startOfMonth();

        while ($current->lte($last)) {

            $key = $current->format('Y-m');

            $months[$key] = [
                'month' => $current->format('M Y'),
                'tasks' => 0,
            ];

            $current->addMonth();
        }

        foreach ($tasks as $task) {

            $start = Carbon::parse($task->start_date)->startOfMonth();
            $end = Carbon::parse($task->due_date)->startOfMonth();

            while ($start->lte($end)) {

                $key = $start->format('Y-m');

                if (isset($months[$key])) {
                    $months[$key]['tasks']++;
                }

                $start->addMonth();
            }
        }

        return array_values($months);
    }
}
