<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_can_be_filtered_by_team_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $projectA->teams()->attach($teamA);
        $projectB->teams()->attach($teamB);

        Task::factory()->create(['project_id' => $projectA->id, 'title' => 'Team A task']);
        Task::factory()->create(['project_id' => $projectB->id, 'title' => 'Team B task']);

        $this->getJson("/api/tasks?team_id={$teamA->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Team A task');
    }

    public function test_task_can_be_updated_via_put_with_route_model_binding(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->create([
            'title' => 'Old title',
            'status' => TaskStatus::Pending->value,
            'progress' => 0,
        ]);

        $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated title',
            'status' => 'in_progress',
            'progress' => 50,
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.progress', 50);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'status' => 'in_progress',
            'progress' => 50,
        ]);
    }

    public function test_task_can_be_partially_updated_with_patch(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->create([
            'title' => 'Keep title',
            'status' => TaskStatus::Pending->value,
        ]);

        $this->patchJson("/api/tasks/{$task->id}", [
            'status' => 'completed',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Keep title')
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_api_task_upd_006_task_start_date_can_be_updated_alone_and_with_other_fields(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-30',
            'title' => 'Original task title',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/tasks/{$task->id}", [
            'start_date' => '2026-07-15',
        ])
            ->assertOk()
            ->assertJsonPath('data.start_date', '2026-07-15');

        $this->assertSame('2026-07-15', $task->fresh()->start_date->toDateString());

        $this->putJson("/api/tasks/{$task->id}", [
            'start_date' => '2026-07-20',
            'title' => 'Updated task title',
        ])
            ->assertOk()
            ->assertJsonPath('data.start_date', '2026-07-20')
            ->assertJsonPath('data.title', 'Updated task title');

        $task->refresh();
        $this->assertSame('2026-07-20', $task->start_date->toDateString());
        $this->assertSame('Updated task title', $task->title);
    }

    public function test_api_bug_018_project_task_analytics_endpoint_is_available(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'created_by' => $user->id,
            'start_date' => '2026-01-01',
            'deadline' => '2026-03-31',
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'start_date' => '2026-02-01',
            'due_date' => '2026-02-15',
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/projects/{$project->id}/analytics/tasks")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.1.month', 'Feb 2026')
            ->assertJsonPath('data.1.tasks', 1);
    }

    public function test_api_bug_019_create_task_uses_the_canonical_title_field_in_request_and_response(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Canonical task title',
            'priority' => 'high',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Canonical task title')
            ->assertJsonPath('data.name', 'Canonical task title');

        $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'name' => 'Alias task name',
            'priority' => 'medium',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Alias task name')
            ->assertJsonPath('data.name', 'Alias task name');

        $this->assertDatabaseHas('tasks', ['title' => 'Canonical task title']);
        $this->assertDatabaseHas('tasks', ['title' => 'Alias task name']);
    }

    public function test_api_task_del_009_project_creator_can_delete_a_task(): void
    {
        $projectCreator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $projectCreator->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        Sanctum::actingAs($projectCreator);

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Task deleted successfully.');

        $this->assertModelMissing($task);
    }

    public function test_api_task_del_009_authenticated_user_cannot_delete_another_users_project_task(): void
    {
        $projectCreator = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $projectCreator->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);
        $task->users()->attach($otherUser);

        Sanctum::actingAs($otherUser);

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertForbidden();

        $this->assertModelExists($task);
    }

    public function test_api_task_del_009_guest_cannot_delete_a_task(): void
    {
        $task = Task::factory()->create();

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertUnauthorized();

        $this->assertModelExists($task);
    }
}
