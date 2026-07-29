<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_team_005_team_members_can_be_removed_when_user_ids_is_sent_in_delete_body(): void
    {
        $actor = User::factory()->create();
        $jsonMember = User::factory()->create();
        $formMember = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach([$jsonMember->id, $formMember->id]);
        Sanctum::actingAs($actor);

        $this->deleteJson("/api/teams/{$team->id}/members", [
            'user_ids' => [$jsonMember->id],
        ])->assertOk();

        $this->assertFalse($team->members()->whereKey($jsonMember->id)->exists());

        $this->delete("/api/teams/{$team->id}/members", [
            'user_ids' => [$formMember->id],
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertFalse($team->members()->whereKey($formMember->id)->exists());

        $indexedMember = User::factory()->create();
        $team->members()->attach($indexedMember);

        $this->call(
            'DELETE',
            "/api/teams/{$team->id}/members",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode(['user_ids' => [$indexedMember->id]]),
        )->assertOk();

        $this->assertFalse($team->members()->whereKey($indexedMember->id)->exists());

        $multipartMember = User::factory()->create();
        $team->members()->attach($multipartMember);
        $boundary = '----PostmanBoundary7MA4YWxkTrZu0gW';
        $multipartBody = implode("\r\n", [
            "--{$boundary}",
            'Content-Disposition: form-data; name="user_ids[0]"',
            '',
            (string) $multipartMember->id,
            "--{$boundary}--",
            '',
        ]);

        $this->call(
            'DELETE',
            "/api/teams/{$team->id}/members",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => "multipart/form-data; boundary={$boundary}",
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_LENGTH' => (string) strlen($multipartBody),
            ],
            $multipartBody,
        )->assertOk();

        $this->assertFalse($team->members()->whereKey($multipartMember->id)->exists());
    }

    public function test_api_bug_020_team_name_update_persists(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['name' => 'old-team-name']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/teams/{$team->id}", [
            'name' => 'updated-team-name',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'updated-team-name');

        $this->assertSame('updated-team-name', $team->fresh()->name);
    }

    public function test_api_bug_021_team_details_returns_existing_members_as_an_array(): void
    {
        $user = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($member);
        Sanctum::actingAs($user);

        $this->getJson("/api/teams/{$team->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.members')
            ->assertJsonPath('data.members.0.id', $member->id);
    }
}
