<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_chat_023_direct_chat_allows_users_who_share_a_team(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $sharedTeam = Team::factory()->create();
        $sharedTeam->members()->attach([$firstUser->id, $secondUser->id]);
        Sanctum::actingAs($firstUser);

        $this->postJson('/api/conversations/direct', [
            'user_id' => $secondUser->id,
        ])
            ->assertCreated()
            ->assertJsonCount(2, 'data.participants');
    }

    public function test_direct_conversation_includes_last_message_when_body_is_sent_or_message_is_posted(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $sharedTeam = Team::factory()->create();
        $sharedTeam->members()->attach([$firstUser->id, $secondUser->id]);
        Sanctum::actingAs($firstUser);

        $createResponse = $this->postJson('/api/conversations/direct', [
            'user_id' => $secondUser->id,
            'body' => 'Hello from create',
        ])
            ->assertCreated()
            ->assertJsonPath('data.last_message.body', 'Hello from create');

        $conversationId = $createResponse->json('data.id');

        $this->postJson("/api/conversations/{$conversationId}/messages", [
            'body' => 'Follow-up message',
        ])->assertCreated();

        $this->getJson("/api/conversations/{$conversationId}")
            ->assertOk()
            ->assertJsonPath('data.last_message.body', 'Follow-up message');
    }

    public function test_access_to_the_same_project_without_a_shared_team_does_not_allow_direct_chat(): void
    {
        $projectCreator = User::factory()->create();
        $projectMember = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $projectCreator->id]);
        $memberTeam = Team::factory()->create();
        $memberTeam->members()->attach($projectMember);
        $project->teams()->attach($memberTeam);
        Sanctum::actingAs($projectCreator);

        $this->postJson('/api/conversations/direct', [
            'user_id' => $projectMember->id,
        ])->assertForbidden();
    }

    public function test_api_conversation_029_project_conversation_get_and_post_with_member_ids(): void
    {
        $projectCreator = User::factory()->create();
        $projectMember = User::factory()->create();
        $extraMember = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $projectCreator->id]);
        $team = Team::factory()->create();
        $team->members()->attach($projectMember);
        $project->teams()->attach($team);
        Sanctum::actingAs($projectCreator);

        $this->getJson("/api/projects/{$project->id}/conversation")
            ->assertOk()
            ->assertJsonPath('data.project.id', $project->id)
            ->assertJsonCount(2, 'data.participants');

        $this->postJson("/api/projects/{$project->id}/conversation", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_ids']);

        $this->postJson("/api/projects/{$project->id}/conversation", [
            'user_ids' => [$extraMember->id],
        ])
            ->assertCreated()
            ->assertJsonPath('data.project.id', $project->id);

        $conversation = Conversation::query()->sole();
        $this->assertEqualsCanonicalizing(
            [$projectCreator->id, $extraMember->id],
            $conversation->participants()->pluck('users.id')->all(),
        );
    }

    public function test_sending_a_message_creates_a_notification_for_other_participants(): void
    {
        Notification::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$sender->id, $recipient->id]);

        Sanctum::actingAs($sender);

        $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'body' => 'Hello from admin',
        ])
            ->assertCreated();

        Notification::assertSentTo($recipient, \App\Notifications\CollaborationDatabaseNotification::class);
    }
}
