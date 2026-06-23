<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch or create roles-based users
        $admin          = User::role('admin')->first()          ?? User::factory()->create(['name' => 'Admin User']);
        $projectManager = User::role('project_manager')->first() ?? User::factory()->create(['name' => 'Project Manager']);

        // Ensure we have some teams
        $teams = Team::all()->count() > 0
            ? Team::all()
            : Team::factory()->count(3)->create();

        // ── Seed 10 projects created by the admin ────────────────────────────
        Project::factory()
            ->count(5)
            ->createdBy($admin)
            ->create()
            ->each(function (Project $project) use ($teams) {
                $project->teams()->sync($teams->random(rand(1, 2))->pluck('id'));
            });

        // ── Seed 5 projects created by the project manager ──────────────────
        Project::factory()
            ->count(3)
            ->createdBy($projectManager)
            ->inProgress()
            ->create()
            ->each(function (Project $project) use ($teams) {
                $project->teams()->sync($teams->random(1)->pluck('id'));
            });

        // ── One critical overdue project ─────────────────────────────────────
        Project::factory()
            ->createdBy($admin)
            ->critical()
            ->create([
                'name'       => 'Critical Launch Project',
                'start_date' => now()->subMonth()->toDateString(),
                'deadline'   => now()->subWeek()->toDateString(),
                'status'     => Project::STATUS_ON_HOLD,
            ]);

        $this->command->info('Projects seeded successfully.');
    }
}