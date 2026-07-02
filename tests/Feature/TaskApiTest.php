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
}
