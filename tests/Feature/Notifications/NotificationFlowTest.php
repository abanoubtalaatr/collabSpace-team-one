<?php

namespace Tests\Feature\Notifications;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_creation_notifies_assigned_users_only(): void
    {
        $actor = User::factory()->create();
        $assignedUser = User::factory()->create();
        $unassignedUser = User::factory()->create();
        $project = Project::factory()->createdBy($actor)->create();

        $this->actingAs($actor, 'sanctum')
            ->postJson('/api/tasks', [
                'project_id' => $project->id,
                'name' => 'Prepare launch notes',
                'description' => 'Draft collaboration launch notes.',
                'status' => TaskStatus::Pending->value,
                'user_ids' => [$assignedUser->id, $actor->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Prepare launch notes');

        $this->assertSame(1, $assignedUser->fresh()->notifications()->count());
        $this->assertSame(0, $actor->fresh()->notifications()->count());
        $this->assertSame(0, $unassignedUser->fresh()->notifications()->count());
        $this->assertSame('task_created', $assignedUser->fresh()->notifications()->first()->data['type']);
    }

    public function test_team_member_add_and_remove_notify_target_user_only(): void
    {
        $actor = User::factory()->create();
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $team = Team::factory()->create();

        $this->actingAs($actor, 'sanctum')
            ->postJson("/api/teams/{$team->id}/members", [
                'user_id' => $targetUser->id,
            ])
            ->assertOk();

        $this->assertSame(1, $targetUser->fresh()->notifications()->count());
        $this->assertSame('team_member_added', $targetUser->fresh()->notifications()->first()->data['type']);
        $this->assertSame(0, $actor->fresh()->notifications()->count());
        $this->assertSame(0, $otherUser->fresh()->notifications()->count());

        $this->actingAs($actor, 'sanctum')
            ->deleteJson("/api/teams/{$team->id}/members/{$targetUser->id}")
            ->assertOk();

        $this->assertSame(2, $targetUser->fresh()->notifications()->count());
        $this->assertContains(
            'team_member_removed',
            $targetUser->fresh()->notifications->pluck('data')->pluck('type')->all()
        );
    }

    public function test_project_team_add_and_remove_notify_team_users_only(): void
    {
        $actor = User::factory()->create();
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach([$targetUser->id, $actor->id]);
        $project = Project::factory()->createdBy($actor)->create();

        $this->actingAs($actor, 'sanctum')
            ->postJson("/api/projects/{$project->id}/teams", [
                'team_id' => $team->id,
            ])
            ->assertOk();

        $this->assertSame(1, $targetUser->fresh()->notifications()->count());
        $this->assertSame('project_member_added', $targetUser->fresh()->notifications()->first()->data['type']);
        $this->assertSame(0, $actor->fresh()->notifications()->count());
        $this->assertSame(0, $otherUser->fresh()->notifications()->count());

        $this->actingAs($actor, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}/teams/{$team->id}")
            ->assertOk();

        $this->assertSame(2, $targetUser->fresh()->notifications()->count());
        $this->assertContains(
            'project_member_removed',
            $targetUser->fresh()->notifications->pluck('data')->pluck('type')->all()
        );
    }

    public function test_project_status_update_notifies_members_and_creator_only_when_status_changes(): void
    {
        $actor = User::factory()->create();
        $actor->addRole(Role::factory()->create([
            'name' => 'admin',
            'display_name' => 'Administrator',
        ]));
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $project = Project::factory()->createdBy($creator)->create([
            'status' => ProjectStatus::PENDING->value,
        ]);
        $team = Team::factory()->create();
        $team->members()->attach([$member->id, $actor->id]);
        $project->teams()->attach($team->id);

        $payload = [
            'name' => $project->name,
            'description' => $project->description,
            'start_date' => $project->start_date->toDateString(),
            'deadline' => $project->deadline->toDateString(),
            'priority' => $project->priority->value,
            'status' => ProjectStatus::IN_PROGRESS->value,
        ];

        $this->actingAs($actor, 'sanctum')
            ->putJson("/api/admin/projects/{$project->id}", $payload)
            ->assertOk();

        $this->assertSame(1, $creator->fresh()->notifications()->count());
        $this->assertSame(1, $member->fresh()->notifications()->count());
        $this->assertSame(0, $actor->fresh()->notifications()->count());
        $this->assertSame(0, $outsider->fresh()->notifications()->count());
        $this->assertSame('project_status_updated', $member->fresh()->notifications()->first()->data['type']);
        $this->assertSame(ProjectStatus::PENDING->value, $member->fresh()->notifications()->first()->data['old_status']);
        $this->assertSame(ProjectStatus::IN_PROGRESS->value, $member->fresh()->notifications()->first()->data['new_status']);

        $this->actingAs($actor, 'sanctum')
            ->putJson("/api/admin/projects/{$project->id}", $payload)
            ->assertOk();

        $this->assertSame(1, $creator->fresh()->notifications()->count());
        $this->assertSame(1, $member->fresh()->notifications()->count());
    }
}
