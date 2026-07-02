<?php

namespace App\Ai\Tools;

use App\Ai\Concerns\ScopesToUser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchTasks implements Tool
{
    use ScopesToUser;

    public function description(): Stringable|string
    {
        return 'Search tasks assigned to the current user (or all tasks they can see) by title, description, and status.';
    }

    public function handle(Request $request): string
    {
        $query = $request->string('query')->value();
        $status = $request->string('status')->value();

        $tasks = $this->user->tasks()
            ->with('project:id,name')
            ->when($query, function (Builder $builder) use ($query) {
                $builder->where(function (Builder $q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                });
            })
            ->when($status, fn (Builder $builder) => $builder->where('status', $status))
            ->latest('updated_at')
            ->limit(15)
            ->get(['id', 'project_id', 'title', 'description', 'status', 'priority', 'progress', 'due_date']);

        if ($tasks->isEmpty()) {
            return 'No tasks found for this user.';
        }

        return $tasks->toJson(JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Optional search text for task title or description')
                ->nullable(),
            'status' => $schema->string()
                ->description('Filter by status: pending, in_progress, in_review, completed')
                ->nullable(),
        ];
    }
}
