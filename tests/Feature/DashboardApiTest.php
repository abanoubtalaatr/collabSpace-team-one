<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
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

        $visibleProject->addMediaFromString('visible file')
            ->usingName('UX_Research_Summary.pdf')
            ->usingFileName('ux-research-summary.pdf')
            ->toMediaCollection(Project::MEDIA_COLLECTION_ATTACHMENTS);
        $hiddenProject->addMediaFromString('hidden file')
            ->usingName('Hidden.pdf')
            ->usingFileName('hidden.pdf')
            ->toMediaCollection(Project::MEDIA_COLLECTION_ATTACHMENTS);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/dashboard/recent-files')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'UX_Research_Summary.pdf')
            ->assertJsonPath('data.0.project_name', 'Alpha')
            ->assertJsonPath('data.0.uploaded_by', 'Mohamed Wahib');
    }

    public function test_project_overview_returns_monthly_data_for_authorized_project(): void
    {
        $admin = $this->userWithRole('admin');
        $project = Project::factory()->create();

        $this->createTask($project, TaskStatus::Completed, now()->month(1)->startOfMonth());
        $this->createTask($project, TaskStatus::Pending, now()->month(1)->startOfMonth()->addDay());
        $this->createTask($project, TaskStatus::Completed, now()->month(2)->startOfMonth());

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/dashboard/project-overview?project_id={$project->id}")
            ->assertOk()
            ->assertJsonPath('data.0.month', 'Jan')
            ->assertJsonPath('data.0.total_tasks', 2)
            ->assertJsonPath('data.0.completed_tasks', 1)
            ->assertJsonPath('data.1.month', 'Feb')
            ->assertJsonPath('data.1.total_tasks', 1)
            ->assertJsonPath('data.1.completed_tasks', 1);
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
            ->assertJsonPath('data.0.total_tasks', 1)
            ->assertJsonPath('data.0.completed_tasks', 1);
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
            ->assertJsonPath('data.0.total_tasks', 0)
            ->assertJsonPath('data.0.completed_tasks', 0)
            ->assertJsonPath('data.11.total_tasks', 0)
            ->assertJsonPath('data.11.completed_tasks', 0);
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => $roleName]);

        $user->addRole($role);

        return $user;
    }

    private function createTask(Project $project, TaskStatus $status, mixed $createdAt = null): Task
    {
        return Task::factory()->create([
            'project_id' => $project->id,
            'status' => $status->value,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }
}
