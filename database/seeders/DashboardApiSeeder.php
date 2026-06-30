<?php

namespace Database\Seeders;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DashboardApiSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::find(1);
        $projectManager = User::find(2);
        $member = User::find(3);

        if (! $admin || ! $projectManager || ! $member) {
            $this->command->warn('Dashboard API seed needs users with ids 1, 2, and 3. Run UserSeeder first.');

            return;
        }

        $this->ensureRoles($admin, $projectManager, $member);

        $alphaTeam = $this->team('dashboard-alpha-team', 'Dashboard Alpha Team');
        $betaTeam = $this->team('dashboard-beta-team', 'Dashboard Beta Team');
        $adminTeam = $this->team('dashboard-admin-team', 'Dashboard Admin Team');

        $alphaTeam->members()->syncWithoutDetaching([$projectManager->id, $member->id]);
        $betaTeam->members()->syncWithoutDetaching([$projectManager->id]);
        $adminTeam->members()->syncWithoutDetaching([$admin->id]);

        $alphaProject = $this->project(
            'Dashboard API Alpha',
            $projectManager,
            'Visible to user 2 as creator and user 3 through team membership.',
            ProjectPriority::HIGH,
            ProjectStatus::IN_PROGRESS,
        );
        $betaProject = $this->project(
            'Dashboard API Beta',
            $projectManager,
            'Visible to user 2 only for manager scoped dashboard checks.',
            ProjectPriority::MEDIUM,
            ProjectStatus::PENDING,
        );
        $adminProject = $this->project(
            'Dashboard API Admin Only',
            $admin,
            'Visible to admin user 1 only in scoped dashboard tests.',
            ProjectPriority::CRITICAL,
            ProjectStatus::ON_HOLD,
        );

        $alphaProject->teams()->syncWithoutDetaching([$alphaTeam->id]);
        $betaProject->teams()->syncWithoutDetaching([$betaTeam->id]);
        $adminProject->teams()->syncWithoutDetaching([$adminTeam->id]);

        $this->task($alphaProject, 'Alpha Pending Dashboard Task', TaskStatus::Pending, 1, [$member->id]);
        $this->task($alphaProject, 'Alpha In Progress Dashboard Task', TaskStatus::InProgress, 1, [$member->id]);
        $this->task($alphaProject, 'Alpha In Review Dashboard Task', TaskStatus::InReview, 2, [$member->id]);
        $this->task($alphaProject, 'Alpha Completed Dashboard Task', TaskStatus::Completed, 2, [$member->id]);
        $this->task($alphaProject, 'Alpha Manager Only Completed Task', TaskStatus::Completed, 3, [$projectManager->id]);

        $this->task($betaProject, 'Beta Pending Dashboard Task', TaskStatus::Pending, 3, [$projectManager->id]);
        $this->task($betaProject, 'Beta Completed Dashboard Task', TaskStatus::Completed, 4, [$projectManager->id]);

        $this->task($adminProject, 'Admin Pending Dashboard Task', TaskStatus::Pending, 5, [$admin->id]);
        $this->task($adminProject, 'Admin Completed Dashboard Task', TaskStatus::Completed, 6, [$admin->id]);

        $this->media($alphaProject, 'Dashboard_Alpha_Brief.pdf', 'dashboard-alpha-brief.pdf');
        $this->media($alphaProject, 'UX_Research_Summary.pdf', 'ux-research-summary.pdf');
        $this->media($betaProject, 'Dashboard_Beta_Report.pdf', 'dashboard-beta-report.pdf');
        $this->media($adminProject, 'Admin_Only_Report.pdf', 'admin-only-report.pdf');

        $this->command->info('Dashboard API fake data seeded successfully.');
    }

    private function ensureRoles(User $admin, User $projectManager, User $member): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Administrator']);
        $managerRole = Role::firstOrCreate(['name' => 'Project'], ['display_name' => 'Project Manager']);
        $memberRole = Role::firstOrCreate(['name' => 'member'], ['display_name' => 'Member']);

        if (! $admin->hasRole('admin')) {
            $admin->addRole($adminRole);
        }

        if (! $projectManager->hasRole('Project')) {
            $projectManager->addRole($managerRole);
        }

        if (! $member->hasRole('member')) {
            $member->addRole($memberRole);
        }
    }

    private function team(string $name, string $displayName): Team
    {
        return Team::firstOrCreate(
            ['name' => $name],
            [
                'display_name' => $displayName,
                'description' => fake()->sentence(12),
            ]
        );
    }

    private function project(
        string $name,
        User $creator,
        string $description,
        ProjectPriority $priority,
        ProjectStatus $status,
    ): Project {
        return Project::updateOrCreate(
            [
                'name' => $name,
                'created_by' => $creator->id,
            ],
            [
                'description' => $description,
                'start_date' => now()->subMonths(fake()->numberBetween(1, 3))->toDateString(),
                'deadline' => now()->addMonths(fake()->numberBetween(1, 4))->toDateString(),
                'priority' => $priority->value,
                'status' => $status->value,
            ]
        );
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function task(Project $project, string $name, TaskStatus $status, int $month, array $userIds): Task
    {
        $createdAt = Carbon::create(now()->year, $month, fake()->numberBetween(1, 20), 10, 0, 0);

        $task = Task::firstOrNew([
            'project_id' => $project->id,
            'name' => $name,
        ]);

        $task->forceFill([
            'description' => fake()->paragraph(),
            'status' => $status->value,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        $task->save();

        $task->users()->syncWithoutDetaching($userIds);

        return $task;
    }

    private function media(Project $project, string $name, string $fileName): void
    {
        $exists = $project->media()
            ->where('collection_name', Project::MEDIA_COLLECTION_ATTACHMENTS)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return;
        }

        $project->addMediaFromString(fake()->paragraphs(3, true))
            ->usingName($name)
            ->usingFileName($fileName)
            ->toMediaCollection(Project::MEDIA_COLLECTION_ATTACHMENTS);
    }
}
