<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by'  => User::inRandomOrder()->first()?->id ?? User::factory(),
            'name'        => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_date'  => $this->faker->date(),
            'deadline'    => $this->faker->dateTimeBetween('+1 month', '+6 months')->format('Y-m-d'),
            'priority'    => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'status'      => $this->faker->randomElement(['pending', 'in_progress', 'on_hold', 'completed', 'cancelled']),
        ];
    }
}
