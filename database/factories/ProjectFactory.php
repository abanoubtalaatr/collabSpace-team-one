<?php

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-3 months', 'now');
        $deadline  = $this->faker->dateTimeBetween($startDate, '+6 months');

        return [
            'name'        => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_date'  => $startDate->format('Y-m-d'),
            'deadline'    => $deadline->format('Y-m-d'),

            'priority' => $this->faker->randomElement(
                ProjectPriority::values()
            ),

            'status' => $this->faker->randomElement(
                ProjectStatus::values()
            ),

            'created_by' => User::factory(),
        ];
    }

    public function pending(): self
    {
        return $this->state([
            'status' => ProjectStatus::PENDING->value,
        ]);
    }

    public function inProgress(): self
    {
        return $this->state([
            'status' => ProjectStatus::IN_PROGRESS->value,
        ]);
    }

    public function completed(): self
    {
        return $this->state([
            'status' => ProjectStatus::COMPLETED->value,
        ]);
    }

    public function critical(): self
    {
        return $this->state([
            'priority' => ProjectPriority::CRITICAL->value,
        ]);
    }

    public function createdBy(User $user): self
    {
        return $this->state([
            'created_by' => $user->id,
        ]);
    }
}