<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();

        $projectManager = User::whereHas('roles', function ($query) {
            $query->where('name', 'project_manager');
        })->first();

        if (! $admin || ! $projectManager) {
            $this->command->warn(
                'Admin or Project Manager users not found. Run UserSeeder first.'
            );

            return;
        }

        // Projects created by admin
        Project::factory()
            ->count(5)
            ->createdBy($admin)
            ->create();

        // Projects created by project manager
        Project::factory()
            ->count(3)
            ->createdBy($projectManager)
            ->inProgress()
            ->create();

        // One critical overdue project
        Project::factory()
            ->createdBy($admin)
            ->critical()
            ->create([
                'name'       => 'Critical Launch Project',
                'start_date' => now()->subMonth()->toDateString(),
                'deadline'   => now()->subWeek()->toDateString(),
                'status'     => ProjectStatus::ON_HOLD->value,
            ]);

        $this->command->info('Projects seeded successfully.');
    }
}