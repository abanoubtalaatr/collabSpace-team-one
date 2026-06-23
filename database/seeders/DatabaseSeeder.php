<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Project::factory(5)->create();

        // Task::factory(30)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


        $teams = Team::factory(3)->create();

        $users = User::factory(10)->create();
        foreach ($users as $user) {
            $user->teams()->attach($teams->random()->id);
        }

        $projects = Project::factory(5)->create()->each(function ($project) use ($teams) {

            $project->teams()->attach($teams->random()->id);
        });

        Task::factory(30)->create();

    }
}
