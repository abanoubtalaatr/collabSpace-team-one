<?php

namespace Tests\Feature;

use App\Enums\FileStatus;
use App\Enums\FileType;
use App\Enums\TaskStatus;
use App\Models\File;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_routes_require_authentication(): void
    {
        $this->getJson('/api/dashboard/overview')->assertUnauthorized();
        $this->getJson('/api/dashboard/stats')->assertUnauthorized();
        $this->getJson('/api/dashboard/recent-files')->assertUnauthorized();
        $this->getJson('/api/dashboard/project-overview?project_id=1')->assertUnauthorized();
    }

    public function test_admin_stats_include_all_tasks(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();

        $this->createTask($project, TaskStatus::Pending);
        $this->createTask($project, TaskStatus::InProgress);
        $this->createTask($project, TaskStatus::InReview);
        $this->createTask($project, TaskStatus::Completed);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.user.name', $admin->name)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'avatar_url'],
                    'progress',
                    'chart_data' => [
                        'status' => [['label', 'key', 'value']],
                        'monthly' => [['month', 'month_number', 'total_tasks', 'completed_tasks', 'progress']],
                    ],
                ],
            ])
            ->assertJsonPath('data.pending_tasks', 1)
            ->assertJsonPath('data.in_progress_tasks', 1)
            ->assertJsonPath('data.in_review_tasks', 1)
            ->assertJsonPath('data.completed_tasks', 1)
            ->assertJsonPath('data.total_tasks', 4)
            ->assertJsonPath('data.completion_rate', 25);
    }

    public function test_project_role_stats_are_scoped_to_created_projects(): void
    {
        $leader = $this->userWithRole('Project');
        $ownProject = Project::factory()->createdBy($leader)->create();
        $otherProject = Project::factory()->create();

        $this->createTask($ownProject, TaskStatus::Pending);
        $this->createTask($ownProject, TaskStatus::Completed);
        $this->createTask($otherProject, TaskStatus::Completed);

        $this->actingAs($leader, 'sanctum')
            ->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.pending_tasks', 1)
            ->assertJsonPath('data.completed_tasks', 1)
            ->assertJsonPath('data.total_tasks', 2)
            ->assertJsonPath('data.completion_rate', 50);
    }

    public function test_user_without_role_gets_accessible_dashboard_stats(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->createdBy($user)->create();
        $teamProject = Project::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user);
        $teamProject->teams()->attach($team);

        $this->createTask($project, TaskStatus::Pending);
        $this->createTask($project, TaskStatus::Completed);
        $this->createTask($teamProject, TaskStatus::InProgress);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.pending_tasks', 1)
            ->assertJsonPath('data.in_progress_tasks', 1)
            ->assertJsonPath('data.completed_tasks', 1)
            ->assertJsonPath('data.total_tasks', 3);
    }

    public function test_member_stats_are_scoped_to_assigned_tasks(): void
    {
        $member = $this->userWithRole('member');
        $project = Project::factory()->create();
        $assignedPending = $this->createTask($project, TaskStatus::Pending);
        $assignedCompleted = $this->createTask($project, TaskStatus::Completed);
        $this->createTask($project, TaskStatus::Completed);

        $assignedPending->users()->attach($member);
        $assignedCompleted->users()->attach($member);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.pending_tasks', 1)
            ->assertJsonPath('data.completed_tasks', 1)
            ->assertJsonPath('data.total_tasks', 2)
            ->assertJsonPath('data.completion_rate', 50);
    }

    public function test_member_recent_files_are_scoped_to_team_projects(): void
    {
        $member = $this->userWithRole('member');
        $creator = User::factory()->create(['name' => 'Mohamed Wahib']);
        $team = Team::factory()->create();
        $team->members()->attach($member);

        $visibleProject = Project::factory()->createdBy($creator)->create(['name' => 'Alpha']);
        $hiddenProject = Project::factory()->create(['name' => 'Hidden']);
        $visibleProject->teams()->attach($team);

        $this->createAttachedProjectFile($member, $visibleProject, 'UX_Research_Summary.pdf');
        $this->createAttachedProjectFile($creator, $hiddenProject, 'Hidden.pdf');

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/dashboard/recent-files')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'UX_Research_Summary.pdf')
            ->assertJsonPath('data.0.project_name', 'Alpha')
            ->assertJsonPath('data.0.uploaded_by', $member->name);
    }

    public function test_stats_progress_uses_average_task_progress(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();

        Task::factory()->create([
            'project_id' => $project->id,
            'progress' => 40,
            'created_at' => now(),
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'progress' => 80,
            'created_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.progress', 60);
    }

    public function test_overview_returns_user_chart_avatars_and_team_members(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create(['name' => 'Overview Project']);
        $team = Team::factory()->create();
        $member = User::factory()->create(['name' => 'Team Member', 'job_title' => 'Developer']);
        $team->members()->attach($member);
        $project->teams()->attach($team);

        $this->createTask($project, TaskStatus::Pending, now(), 25);
        $this->createTask($project, TaskStatus::Completed, now(), 75);

        $project->addMediaFromString('overview file')
            ->usingName('Overview.pdf')
            ->usingFileName('overview.pdf')
            ->toMediaCollection(Project::MEDIA_COLLECTION_ATTACHMENTS);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/dashboard/overview?project_id={$project->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'avatar_url'],
                    'project' => ['id', 'name', 'status', 'progress'],
                    'stats' => [
                        'pending_tasks',
                        'in_progress_tasks',
                        'in_review_tasks',
                        'completed_tasks',
                        'total_tasks',
                        'progress',
                        'completion_rate',
                    ],
                    'chart_data' => [
                        'status',
                        'monthly',
                    ],
                    'recent_files',
                    'team_members' => [['id', 'name', 'email', 'job_title', 'avatar_url']],
                ],
            ])
            ->assertJsonPath('data.project.name', 'Overview Project')
            ->assertJsonPath('data.stats.total_tasks', 2)
            ->assertJsonPath('data.stats.progress', 50)
            ->assertJsonPath('data.team_members.0.name', 'Team Member');
    }

    public function test_project_overview_returns_monthly_data_for_authorized_project(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();
        $year = now()->year;

        $this->createTask($project, TaskStatus::Completed, now()->setDate($year, 1, 5), 100);
        $this->createTask($project, TaskStatus::Pending, now()->setDate($year, 1, 10), 20);
        $this->createTask($project, TaskStatus::Completed, now()->setDate($year, 2, 5), 90);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/dashboard/project-overview?project_id={$project->id}")
            ->assertOk()
            ->assertJsonPath('data.user.avatar_url', fn (string $url) => str_contains($url, 'ui-avatars.com'))
            ->assertJsonPath('data.chart_data.monthly.0.month', 'Jan')
            ->assertJsonPath('data.chart_data.monthly.0.total_tasks', 2)
            ->assertJsonPath('data.chart_data.monthly.0.completed_tasks', 1)
            ->assertJsonPath('data.chart_data.monthly.0.progress', 60)
            ->assertJsonPath('data.chart_data.monthly.1.month', 'Feb')
            ->assertJsonPath('data.chart_data.monthly.1.total_tasks', 1)
            ->assertJsonPath('data.chart_data.monthly.1.completed_tasks', 1)
            ->assertJsonPath('data.chart_data.monthly.1.progress', 90);
    }

    public function test_member_project_overview_only_counts_assigned_tasks(): void
    {
        $member = $this->userWithRole('Member');
        $team = Team::factory()->create();
        $team->members()->attach($member);
        $project = Project::factory()->create();
        $project->teams()->attach($team);

        $assigned = $this->createTask($project, TaskStatus::Completed, now()->month(1)->startOfMonth());
        $this->createTask($project, TaskStatus::Completed, now()->month(1)->startOfMonth());
        $assigned->users()->attach($member);

        $this->actingAs($member, 'sanctum')
            ->getJson("/api/dashboard/project-overview?project_id={$project->id}")
            ->assertOk()
            ->assertJsonPath('data.chart_data.monthly.0.total_tasks', 1)
            ->assertJsonPath('data.chart_data.monthly.0.completed_tasks', 1);
    }

    public function test_project_overview_rejects_unauthorized_projects(): void
    {
        $leader = $this->userWithRole('Project');
        $project = Project::factory()->create();

        $this->actingAs($leader, 'sanctum')
            ->getJson("/api/dashboard/project-overview?project_id={$project->id}")
            ->assertForbidden();
    }

    public function test_project_overview_returns_not_found_for_missing_project(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/dashboard/project-overview?project_id=999')
            ->assertNotFound();
    }

    public function test_project_overview_returns_zeroes_when_project_has_no_tasks(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/dashboard/project-overview?project_id={$project->id}")
            ->assertOk()
            ->assertJsonPath('data.chart_data.monthly.0.total_tasks', 0)
            ->assertJsonPath('data.chart_data.monthly.0.completed_tasks', 0)
            ->assertJsonPath('data.chart_data.monthly.11.total_tasks', 0)
            ->assertJsonPath('data.chart_data.monthly.11.completed_tasks', 0);
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => $roleName]);

        $user->addRole($role);

        return $user;
    }

    private function createTask(Project $project, TaskStatus $status, mixed $createdAt = null, int $progress = 0): Task
    {
        return Task::factory()->create([
            'project_id' => $project->id,
            'status' => $status->value,
            'progress' => $progress,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }

    private function createAttachedProjectFile(User $uploader, Project $project, string $name): File
    {
        return File::create([
            'user_id' => $uploader->id,
            'name' => $name,
            'original_name' => strtolower(str_replace(' ', '-', $name)),
            'file_name' => 'files/test/'.strtolower(str_replace(' ', '-', $name)),
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_type' => FileType::Pdf,
            'size' => 128,
            'status' => FileStatus::Attached,
            'attachable_type' => 'project',
            'attachable_id' => $project->id,
        ]);
    }
}
