<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchedulingDateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_creation_rejects_past_start_dates_and_deadlines(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Past project',
            'start_date' => now()->subDays(2)->toDateString(),
            'deadline' => now()->subDay()->toDateString(),
            'priority' => 'high',
            'status' => 'pending',
        ];

        $this->postJson('/api/projects', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'deadline']);

        $user->addRole(Role::factory()->create(['name' => 'admin']));
        $this->postJson('/api/admin/projects', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'deadline']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_project_creation_accepts_today_with_a_future_deadline(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/projects', [
            'name' => 'Current project',
            'description' => 'Project starting today.',
            'start_date' => now()->toDateString(),
            'deadline' => now()->addWeek()->toDateString(),
            'priority' => 'high',
            'status' => 'pending',
        ])->assertCreated();

        $this->assertSame(now()->toDateString(), Project::query()->sole()->start_date->toDateString());
    }

    public function test_all_task_creation_routes_reject_a_past_start_date(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->createdBy($user)->create();
        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Past task',
            'start_date' => now()->subDay()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'high',
        ];

        $this->postJson('/api/tasks', [
            ...$payload,
            'project_id' => $project->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('start_date');

        $this->postJson("/api/projects/{$project->id}/tasks", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('start_date');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_task_creation_accepts_today_with_a_future_due_date(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->createdBy($user)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Current task',
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'high',
        ])->assertCreated();

        $this->assertSame(now()->toDateString(), Task::query()->sole()->start_date->toDateString());
    }

    public function test_task_creation_rejects_a_due_date_before_the_start_date(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->createdBy($user)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Invalid task range',
            'start_date' => now()->addDays(2)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'high',
        ])->assertUnprocessable()->assertJsonValidationErrors('due_date');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_meeting_creation_rejects_a_past_start_time(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/meetings', [
            'title' => 'Past meeting',
            'starts_at' => now()->subHours(2)->toDateTimeString(),
            'ends_at' => now()->subHour()->toDateTimeString(),
            'user_ids' => [$user->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('starts_at');

        $this->assertDatabaseCount('meetings', 0);
    }
}
