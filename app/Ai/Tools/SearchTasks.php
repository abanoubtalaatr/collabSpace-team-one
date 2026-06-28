<?php

namespace App\Ai\Tools;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchTasks implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search for tasks by title, description, and optionally by status.
            Use this tool whenever the user asks about tasks, wants to find specific tasks,
            or asks for open, completed, or in-progress tasks.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): string
    {
        $tasks = Task::query()
            ->where(function ($query) use ($request) {
                $query->where('title', 'like', "%{$request->string('query')}%")
                    ->orWhere('description', 'like', "%{$request->string('query')}%");
            })
            ->where('status', $request->string('status'))
            ->limit(10)
            ->get([
                'id',
                'project_id',
                'title',
                'description',
            ]);

        if ($tasks->isEmpty()) {
            return 'No tasks found.';
        }

        return $tasks->toJson(JSON_PRETTY_PRINT);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value' => $schema->string()->required(),
            'query' => $schema->string()
                ->description('Search text for the task name or description')
                ->required(),
            'status' => $schema->string()
                ->description('Filter tasks by status. Allowed values: pending, in_progress, in_review, completed.'),
        ];
    }
}
