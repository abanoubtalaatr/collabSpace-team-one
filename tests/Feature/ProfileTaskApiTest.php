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

class ProfileTaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_profile_task_010_status_filter_route_returns_only_matching_tasks(): void
    {
        [$user, $pendingTask] = $this->userWithTask(TaskStatus::Pending);
        $completedTask = Task::factory()->create(['status' => TaskStatus::Completed->value]);
        $completedTask->users()->attach($user);
        Sanctum::actingAs($user);

        $this->getJson('/api/profile/tasks?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingTask->id)
            ->assertJsonMissing(['id' => $completedTask->id]);
    }

    public function test_api_bug_summary_012_task_summary_route_is_available(): void
    {
        [$user] = $this->userWithTask(TaskStatus::Pending);
        Sanctum::actingAs($user);

        $this->getJson('/api/profile/tasks/summary')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['to_do', 'in_progress', 'done', 'total'],
            ]);
    }

    public function test_api_bug_list_all_my_tasks_013_returns_the_authenticated_users_paginated_tasks(): void
    {
        [$user, $firstTask] = $this->userWithTask(TaskStatus::Pending);
        $secondTask = Task::factory()->create(['status' => TaskStatus::Completed->value]);
        $secondTask->users()->attach($user);
        $otherUsersTask = Task::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/profile/tasks')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $firstTask->id])
            ->assertJsonFragment(['id' => $secondTask->id])
            ->assertJsonMissing(['id' => $otherUsersTask->id])
            ->assertJsonStructure(['links', 'meta']);
    }

    public function test_api_bug_014_done_classification_returns_only_completed_tasks(): void
    {
        $this->assertClassificationReturnsOnlyStatus('done', TaskStatus::Completed);
    }

    public function test_api_bug_015_in_progress_classification_returns_only_in_progress_tasks(): void
    {
        $this->assertClassificationReturnsOnlyStatus('in_progress', TaskStatus::InProgress);
    }

    public function test_api_bug_016_to_do_classification_returns_only_pending_tasks(): void
    {
        $this->assertClassificationReturnsOnlyStatus('to_do', TaskStatus::Pending);
    }

    public function test_api_sum_024_task_summary_counts_each_accessible_task_once_across_all_access_sources(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $assignedTask = Task::factory()->create([
            'project_id' => Project::factory()->create(['created_by' => $otherUser->id])->id,
            'status' => TaskStatus::Pending->value,
        ]);
        $assignedTask->users()->attach($user);

        Task::factory()->create([
            'project_id' => Project::factory()->create(['created_by' => $user->id])->id,
            'status' => TaskStatus::InProgress->value,
        ]);

        $team = Team::factory()->create();
        $team->members()->attach($user);
        $teamProject = Project::factory()->create(['created_by' => $otherUser->id]);
        $teamProject->teams()->attach($team);
        Task::factory()->create([
            'project_id' => $teamProject->id,
            'status' => TaskStatus::InReview->value,
        ]);

        $overlappingTask = Task::factory()->create([
            'project_id' => Project::factory()->create(['created_by' => $user->id])->id,
            'status' => TaskStatus::Completed->value,
        ]);
        $overlappingTask->users()->attach($user);

        Task::factory()->create([
            'project_id' => Project::factory()->create(['created_by' => $otherUser->id])->id,
            'status' => TaskStatus::Pending->value,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile/tasks/summary')
            ->assertOk()
            ->assertJsonPath('data.to_do', 1)
            ->assertJsonPath('data.in_progress', 1)
            ->assertJsonPath('data.in_review', 1)
            ->assertJsonPath('data.completed', 1)
            ->assertJsonPath('data.done', 1)
            ->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.by_status.pending', 1);

        $this->getJson('/api/profile/tasks')
            ->assertOk()
            ->assertJsonCount(4, 'data');

        $this->getJson('/api/profile/tasks?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedTask->id);

        $this->getJson('/api/profile/tasks?classification=in_progress')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', TaskStatus::InProgress->value);
    }

    /**
     * @return array{User, Task}
     */
    private function userWithTask(TaskStatus $status): array
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['status' => $status->value]);
        $task->users()->attach($user);

        return [$user, $task];
    }

    private function assertClassificationReturnsOnlyStatus(string $classification, TaskStatus $status): void
    {
        [$user, $matchingTask] = $this->userWithTask($status);
        $nonMatchingStatus = $status === TaskStatus::Completed
            ? TaskStatus::Pending
            : TaskStatus::Completed;
        $nonMatchingTask = Task::factory()->create(['status' => $nonMatchingStatus->value]);
        $nonMatchingTask->users()->attach($user);
        Sanctum::actingAs($user);

        $this->getJson("/api/profile/tasks?classification={$classification}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingTask->id)
            ->assertJsonPath('data.0.status', $status->value)
            ->assertJsonMissing(['id' => $nonMatchingTask->id]);
    }
}
