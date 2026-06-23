<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $displayName = fake()->unique()->company().' Team';

        return [
            'name' => Str::slug($displayName).'-'.fake()->unique()->numberBetween(100, 999),
            'display_name' => $displayName,
            'description' => fake()->sentence(12),
        ];
    }
}
