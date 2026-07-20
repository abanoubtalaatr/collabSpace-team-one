<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Notifications\MeetingInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeetingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_bug_017_update_removes_users_omitted_from_the_explicit_participant_list(): void
    {
        $creator = User::factory()->create();
        $directParticipant = User::factory()->create();
        $removedDirectParticipant = User::factory()->create();
        $removedTeamMember = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($removedTeamMember);

        $meeting = Meeting::query()->create([
            'title' => 'Participant removal test',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'created_by' => $creator->id,
        ]);
        $meeting->users()->attach([
            $creator->id,
            $directParticipant->id,
            $removedDirectParticipant->id,
            $removedTeamMember->id,
        ]);
        $meeting->teams()->attach($team);

        Notification::fake();
        Sanctum::actingAs($creator);

        $this->patchJson("/api/meetings/{$meeting->id}", [
            'user_ids' => [$directParticipant->id],
        ])->assertOk();

        $this->assertEqualsCanonicalizing(
            [$creator->id, $directParticipant->id],
            $meeting->users()->pluck('users.id')->all(),
        );
        $this->assertTrue($meeting->teams()->whereKey($team->id)->exists());
        Notification::assertSentTo($directParticipant, MeetingInvitationNotification::class);
        Notification::assertNotSentTo($removedDirectParticipant, MeetingInvitationNotification::class);
        Notification::assertNotSentTo($removedTeamMember, MeetingInvitationNotification::class);
    }

    public function test_explicit_user_list_keeps_a_participant_who_is_also_a_team_member_without_duplicates(): void
    {
        $creator = User::factory()->create();
        $teamMember = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($teamMember);

        $meeting = Meeting::query()->create([
            'title' => 'Participant overlap test',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'created_by' => $creator->id,
        ]);
        $meeting->users()->attach([$creator->id, $teamMember->id]);
        $meeting->teams()->attach($team);

        Notification::fake();
        Sanctum::actingAs($creator);

        $this->patchJson("/api/meetings/{$meeting->id}", [
            'user_ids' => [$teamMember->id],
        ])->assertOk();

        $this->assertEqualsCanonicalizing(
            [$creator->id, $teamMember->id],
            $meeting->users()->pluck('users.id')->all(),
        );
        Notification::assertSentTo($teamMember, MeetingInvitationNotification::class);
    }

    public function test_api_meeting_026_create_requires_project_team_or_member_scope(): void
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();
        $project = Project::factory()->create();
        $team = Team::factory()->create();
        Sanctum::actingAs($creator);

        $this->postJson('/api/meetings', $this->meetingPayload([
            'user_ids' => [],
            'team_ids' => [],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['participants']);

        $this->postJson('/api/meetings', $this->meetingPayload([
            'project_id' => $project->id,
        ]))->assertCreated();

        $this->postJson('/api/meetings', $this->meetingPayload([
            'user_ids' => [$participant->id],
        ]))->assertCreated();

        $this->postJson('/api/meetings', $this->meetingPayload([
            'team_ids' => [$team->id],
        ]))->assertCreated();

        $this->postJson('/api/meetings', $this->meetingPayload([
            'project_id' => $project->id,
            'user_ids' => [$participant->id],
            'team_ids' => [$team->id],
        ]))->assertCreated();
    }

    public function test_api_meeting_027_update_rejects_past_dates_and_accepts_future_dates(): void
    {
        $creator = User::factory()->create();
        $meeting = Meeting::query()->create([
            'title' => 'Date validation test',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
            'created_by' => $creator->id,
        ]);
        $meeting->users()->attach($creator);
        Sanctum::actingAs($creator);

        $this->patchJson("/api/meetings/{$meeting->id}", [
            'starts_at' => now()->subHour()->toDateTimeString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);

        $this->patchJson("/api/meetings/{$meeting->id}", [
            'ends_at' => now()->addDay()->toDateTimeString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ends_at']);

        $newStart = now()->addDays(3);
        $newEnd = now()->addDays(3)->addHour();

        $this->patchJson("/api/meetings/{$meeting->id}", [
            'starts_at' => $newStart->toDateTimeString(),
            'ends_at' => $newEnd->toDateTimeString(),
        ])->assertOk();

        $this->patchJson("/api/meetings/{$meeting->id}", [
            'title' => 'Date validation still valid',
        ])->assertOk();
    }

    public function test_api_meeting_028_upcoming_meetings_rejects_excessive_days_and_enforces_bounds(): void
    {
        $creator = User::factory()->create();
        $insideDefaultWindow = Meeting::query()->create([
            'title' => 'Inside default window',
            'starts_at' => now()->addDays(6),
            'ends_at' => now()->addDays(6)->addHour(),
            'created_by' => $creator->id,
        ]);
        $outsideDefaultWindow = Meeting::query()->create([
            'title' => 'Outside default window',
            'starts_at' => now()->addDays(8),
            'ends_at' => now()->addDays(8)->addHour(),
            'created_by' => $creator->id,
        ]);
        Sanctum::actingAs($creator);

        foreach ([-1, 0, 366, 'invalid'] as $invalidDays) {
            $this->getJson("/api/meetings/upcoming?days={$invalidDays}")
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['days']);
        }

        $this->getJson('/api/meetings/upcoming')
            ->assertOk()
            ->assertJsonCount(1, 'data.meetings')
            ->assertJsonPath('data.meetings.0.id', $insideDefaultWindow->id);

        $this->getJson('/api/meetings/upcoming?days=1')
            ->assertOk()
            ->assertJsonCount(0, 'data.meetings');

        $this->getJson('/api/meetings/upcoming?days=365')
            ->assertOk()
            ->assertJsonCount(2, 'data.meetings')
            ->assertJsonPath('data.meetings.1.id', $outsideDefaultWindow->id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function meetingPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Scoped meeting',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
        ], $overrides);
    }
}
