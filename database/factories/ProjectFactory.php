<?php

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '+1 month');
        $deadline = fake()->dateTimeBetween($startDate, '+9 months');

        return [
            'created_by' => User::factory(),
            'name' => fake()->catchPhrase(),
            'description' => fake()->paragraphs(2, true),
            'start_date' => $startDate,
            'deadline' => $deadline,
            'priority' => fake()->randomElement(ProjectPriority::cases())->value,
            'status' => fake()->randomElement(ProjectStatus::cases())->value,
        ];
    }
}
