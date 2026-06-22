<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class GlobalSearchDemoSeeder extends Seeder
{
    /**
     * Seed searchable demo data.
     */
    public function run(): void
    {
        $roles = Role::factory()
            ->count(10)
            ->sequence(...$this->roleSequence())
            ->create();

        $teams = Team::factory()->count(20)->create();
        $users = User::factory()->count(100)->create();

        $projects = Project::factory()
            ->count(50)
            ->state(fn (): array => [
                'creatd_by' => $users->random()->id,
            ])
            ->create();

        $tasks = Task::factory()
            ->count(500)
            ->state(fn (): array => [
                'project_id' => $projects->random()->id,
            ])
            ->create();

        $teams->each(function (Team $team) use ($users): void {
            $team->members()->attach(
                $users->random(random_int(3, 8))->pluck('id')->all()
            );
        });

        $projects->each(function (Project $project) use ($teams): void {
            $project->teams()->attach(
                $teams->random(random_int(1, 3))->pluck('id')->all()
            );
        });

        $tasks->each(function (Task $task) use ($users): void {
            $task->users()->attach(
                $users->random(random_int(1, 3))->pluck('id')->all()
            );
        });

        $users->each(function (User $user) use ($roles): void {
            $user->syncRolesWithoutDetaching(
                $roles->random(random_int(1, 2))->pluck('id')->all()
            );
        });
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function roleSequence(): array
    {
        return [
            ['name' => 'owner', 'display_name' => 'Owner'],
            ['name' => 'administrator', 'display_name' => 'Administrator'],
            ['name' => 'project-manager', 'display_name' => 'Project Manager'],
            ['name' => 'technical-lead', 'display_name' => 'Technical Lead'],
            ['name' => 'developer', 'display_name' => 'Developer'],
            ['name' => 'designer', 'display_name' => 'Designer'],
            ['name' => 'qa-engineer', 'display_name' => 'QA Engineer'],
            ['name' => 'business-analyst', 'display_name' => 'Business Analyst'],
            ['name' => 'client-stakeholder', 'display_name' => 'Client Stakeholder'],
            ['name' => 'viewer', 'display_name' => 'Viewer'],
        ];
    }
}
