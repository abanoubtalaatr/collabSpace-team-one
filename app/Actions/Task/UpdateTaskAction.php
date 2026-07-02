<?php

namespace App\Actions\Task;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTaskAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $attributes = collect($data)->except('user_ids')->all();

            if ($attributes !== []) {
                $task->update($attributes);
            }

            if (array_key_exists('user_ids', $data)) {
                $task->users()->sync($data['user_ids'] ?? []);
            }

            return $task->fresh(['project', 'users']);
        });
    }
}
