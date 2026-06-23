<?php

namespace Database\Factories;

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
            'priority'    => $this->faker->randomElement(Project::priorities()),
            'status'      => $this->faker->randomElement(Project::statuses()),
            'created_by'  => User::factory(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | States
    |--------------------------------------------------------------------------
    */

    public function pending(): self
    {
        return $this->state(['status' => Project::STATUS_PENDING]);
    }

    public function inProgress(): self
    {
        return $this->state(['status' => Project::STATUS_IN_PROGRESS]);
    }

    public function completed(): self
    {
        return $this->state(['status' => Project::STATUS_COMPLETED]);
    }

    public function critical(): self
    {
        return $this->state(['priority' => Project::PRIORITY_CRITICAL]);
    }

    public function createdBy(User $user): self
    {
        return $this->state(['created_by' => $user->id]);
    }
}