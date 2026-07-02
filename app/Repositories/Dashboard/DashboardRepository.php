<?php

namespace App\Repositories\Dashboard;

use App\Models\File;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DashboardRepository
{
    /**
     * @return Builder<Task>
     */
    public function taskScopeFor(User $user, string $role): Builder
    {
        return match ($role) {
            'admin' => Task::query(),
            'project' => Task::query()->forProjectCreator((int) $user->id),
            'member' => Task::query()->assignedToUser((int) $user->id),
            default => $this->accessibleTasksQuery($user),
        };
    }

    /**
     * @return Builder<Project>
     */
    public function projectScopeFor(User $user, string $role): Builder
    {
        return match ($role) {
            'admin' => Project::query(),
            'project' => Project::query()->createdBy((int) $user->id),
            'member' => Project::query()->forTeamMember((int) $user->id),
            default => $this->accessibleProjectsQuery($user),
        };
    }

    public function findProject(int $projectId): ?Project
    {
        return Project::query()->find($projectId);
    }

    public function userCanAccessProject(User $user, string $role, Project $project): bool
    {
        return match ($role) {
            'admin' => true,
            'project' => (int) $project->created_by === (int) $user->id,
            'member' => $project->teams()->whereHas('members', fn (Builder $query): Builder => $query->where('users.id', $user->id))->exists(),
            default => (int) $project->created_by === (int) $user->id
                || $project->teams()->whereHas('members', fn (Builder $query): Builder => $query->where('users.id', $user->id))->exists(),
        };
    }

    /**
     * @return Collection<int, Task>
     */
    public function overviewTasks(User $user, string $role, Project $project): Collection
    {
        $query = Task::query()
            ->select(['id', 'project_id', 'status', 'progress', 'created_at'])
            ->where('project_id', $project->id)
            ->whereYear('created_at', now()->year);

        if ($role === 'member') {
            $query->assignedToUser((int) $user->id);
        }

        return $query->get();
    }

    /**
     * @return Builder<Task>
     */
    public function taskScopeForProject(User $user, string $role, Project $project): Builder
    {
        $query = Task::query()->where('project_id', $project->id);

        if ($role === 'member') {
            $query->assignedToUser((int) $user->id);
        }

        return $query;
    }

    /**
     * @return Collection<int, File|Media>
     */
    public function recentFilesForProject(Project $project, ?User $user = null, int $limit = 10): Collection
    {
        return $this->collectRecentFiles($user, collect([$project->id]), $limit);
    }

    /**
     * @return Collection<int, File|Media>
     */
    public function recentFiles(User $user, string $role, int $limit = 10): Collection
    {
        $projectIds = $this->projectScopeFor($user, $role)->pluck('id');

        return $this->collectRecentFiles($user, $projectIds, $limit);
    }

    /**
     * @param  Collection<int, int|string>  $projectIds
     * @return Collection<int, File|Media>
     */
    private function collectRecentFiles(?User $user, Collection $projectIds, int $limit): Collection
    {
        $items = collect();

        if ($user instanceof User) {
            $items = $items->concat(
                File::query()
                    ->with($this->fileRelations())
                    ->where('user_id', $user->id)
                    ->get()
            );

            $items = $items->concat(
                $this->userProfileMediaQuery($user)->get()
            );
        }

        if ($projectIds->isNotEmpty()) {
            $taskIds = Task::query()
                ->whereIn('project_id', $projectIds)
                ->pluck('id');

            $items = $items->concat(
                File::query()
                    ->with($this->fileRelations())
                    ->where(function (Builder $query) use ($projectIds, $taskIds): void {
                        $query->where(function (Builder $projectFiles) use ($projectIds): void {
                            $projectFiles->whereIn('attachable_type', $this->morphTypes('project'))
                                ->whereIn('attachable_id', $projectIds);
                        });

                        if ($taskIds->isNotEmpty()) {
                            $query->orWhere(function (Builder $taskFiles) use ($taskIds): void {
                                $taskFiles->whereIn('attachable_type', $this->morphTypes('task'))
                                    ->whereIn('attachable_id', $taskIds);
                            });
                        }
                    })
                    ->get()
            );

            $items = $items->concat(
                Media::query()
                    ->with(['model.creator.media'])
                    ->whereIn('model_type', $this->morphTypes('project'))
                    ->whereIn('model_id', $projectIds)
                    ->where('collection_name', Project::MEDIA_COLLECTION_ATTACHMENTS)
                    ->get()
            );
        }

        return $items
            ->unique(fn (File|Media $item): string => ($item instanceof File ? 'file' : 'media').'-'.$item->id)
            ->sortByDesc(fn (File|Media $item) => $item->created_at)
            ->take($limit)
            ->values();
    }

    /**
     * @return Builder<Media>
     */
    private function userProfileMediaQuery(User $user): Builder
    {
        return Media::query()
            ->with(['model.media'])
            ->where('model_id', $user->id)
            ->where('collection_name', User::MEDIA_COLLECTION_FILES)
            ->whereIn('model_type', $this->morphTypes('user'));
    }

    /**
     * @return array<int, string>
     */
    private function morphTypes(string $alias): array
    {
        return match ($alias) {
            'project' => ['project', Project::class],
            'task' => ['task', Task::class],
            'user' => ['user', User::class],
            default => [$alias],
        };
    }

    /**
     * @return array<int|string, mixed>
     */
    private function fileRelations(): array
    {
        return [
            'uploader:id,name,email',
            'uploader.media',
            'attachable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Task::class => ['project:id,name'],
                ]);
            },
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function projectTeamMembers(Project $project): Collection
    {
        $project->load(['teams.members:id,name,email,job_title']);

        return $project->teams
            ->flatMap(fn ($team) => $team->members)
            ->unique('id')
            ->values();
    }

    /**
     * @return Builder<Task>
     */
    private function accessibleTasksQuery(User $user): Builder
    {
        return Task::query()->where(function (Builder $query) use ($user): void {
            $query->whereHas('users', fn (Builder $userQuery): Builder => $userQuery->where('users.id', $user->id))
                ->orWhereHas('project', fn (Builder $projectQuery): Builder => $projectQuery->where('created_by', $user->id))
                ->orWhereHas('project.teams.members', fn (Builder $memberQuery): Builder => $memberQuery->where('users.id', $user->id));
        });
    }

    /**
     * @return Builder<Project>
     */
    private function accessibleProjectsQuery(User $user): Builder
    {
        return Project::query()->where(function (Builder $query) use ($user): void {
            $query->where('created_by', $user->id)
                ->orWhereHas('teams.members', fn (Builder $memberQuery): Builder => $memberQuery->where('users.id', $user->id));
        });
    }
}
