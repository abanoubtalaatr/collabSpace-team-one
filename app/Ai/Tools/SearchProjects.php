<?php

namespace App\Ai\Tools;

use App\Ai\Concerns\ScopesToUser;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchProjects implements Tool
{
    use ScopesToUser;

    public function description(): Stringable|string
    {
        return 'Search projects the user created or can access via their teams. Filter by name, status, or priority.';
    }

    public function handle(Request $request): string
    {
        $query = $request->string('query')->value();
        $status = $request->string('status')->value();

        $projects = Project::query()
            ->where(function (Builder $builder) {
                $builder->where('created_by', $this->user->id)
                    ->orWhereHas('teams.members', fn ($q) => $q->where('users.id', $this->user->id));
            })
            ->when($query, fn (Builder $builder) => $builder->where('name', 'like', "%{$query}%"))
            ->when($status, fn (Builder $builder) => $builder->where('status', $status))
            ->latest()
            ->limit(15)
            ->get(['id', 'name', 'description', 'status', 'priority', 'start_date', 'deadline', 'created_by']);

        if ($projects->isEmpty()) {
            return 'No projects found for this user.';
        }

        return $projects->toJson(JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Optional search text for project name')
                ->nullable(),
            'status' => $schema->string()
                ->description('Filter: pending, in_progress, on_hold, completed, cancelled')
                ->nullable(),
        ];
    }
}
