<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id'  => Project::inRandomOrder()->first()?->id ?? Project::factory(),
            'name'        => $this->faker->sentence(4),
            'description' => $this->faker->text(),
            'status'      => $this->faker->randomElement(['pending', 'in_progress', 'in_review', 'completed']),
        ];
    }
}
