<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', 'now');
        $dueDate = fake()->dateTimeBetween($startDate, '+2 months');

        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(2, true),
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'progress' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(TaskStatus::cases())->value,
            'priority' => fake()->randomElement(TaskPriority::cases())->value,
        ];
    }
}
