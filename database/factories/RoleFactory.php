<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $displayName = fake()->unique()->jobTitle();

        return [
            'name' => fake()->unique()->slug(2),
            'display_name' => $displayName,
            'description' => fake()->sentence(10),
        ];
    }
}
