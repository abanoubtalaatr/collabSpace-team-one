<?php

namespace Tests\Feature\Notifications;

use App\DTOs\NotificationData;
use App\Models\User;
use App\Notifications\CollaborationDatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_notifications(): void
    {
        $user = User::factory()->create();
        $this->sendNotification($user, 'task_created');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Task created')
            ->assertJsonPath('data.0.type', 'task_created');
    }

    public function test_authenticated_user_only_lists_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownNotification = $this->sendNotification($user, 'task_created');
        $otherNotification = $this->sendNotification($otherUser, 'project_member_added');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownNotification->id)
            ->assertJsonMissing([
                'id' => $otherNotification->id,
            ]);
    }

    public function test_authenticated_user_can_list_unread_notifications(): void
    {
        $user = User::factory()->create();
        $read = $this->sendNotification($user, 'task_created');
        $this->sendNotification($user, 'project_member_added');
        $read->markAsRead();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'project_member_added');
    }

    public function test_authenticated_user_only_lists_their_own_unread_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownNotification = $this->sendNotification($user, 'task_created');
        $otherNotification = $this->sendNotification($otherUser, 'project_member_added');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownNotification->id)
            ->assertJsonMissing([
                'id' => $otherNotification->id,
            ]);
    }

    public function test_authenticated_user_can_get_unread_count(): void
    {
        $user = User::factory()->create();
        $read = $this->sendNotification($user, 'task_created');
        $this->sendNotification($user, 'project_member_added');
        $read->markAsRead();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);
    }

    public function test_authenticated_user_unread_count_excludes_other_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->sendNotification($user, 'task_created');
        $this->sendNotification($otherUser, 'project_member_added');
        $this->sendNotification($otherUser, 'project_member_removed');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 1);
    }

    public function test_authenticated_user_can_show_one_notification(): void
    {
        $user = User::factory()->create();
        $notification = $this->sendNotification($user, 'task_created');

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/notifications/{$notification->id}")
            ->assertOk()
            ->assertJsonPath('id', $notification->id)
            ->assertJsonPath('type', 'task_created');
    }

    public function test_authenticated_user_cannot_show_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = $this->sendNotification($owner, 'task_created');

        $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/notifications/{$notification->id}")
            ->assertNotFound();
    }

    public function test_authenticated_user_can_mark_one_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->sendNotification($user, 'task_created');

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_authenticated_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $this->sendNotification($user, 'task_created');
        $this->sendNotification($user, 'project_member_added');

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('message', 'All notifications marked as read.');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_mark_all_as_read_does_not_mark_other_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->sendNotification($user, 'task_created');
        $this->sendNotification($otherUser, 'project_member_added');

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $otherUser->fresh()->unreadNotifications()->count());
    }

    public function test_authenticated_user_can_delete_one_notification(): void
    {
        $user = User::factory()->create();
        $notification = $this->sendNotification($user, 'task_created');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/notifications/{$notification->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Notification deleted successfully.');

        $this->assertSame(0, $user->fresh()->notifications()->count());
    }

    public function test_authenticated_user_can_delete_all_read_notifications(): void
    {
        $user = User::factory()->create();
        $read = $this->sendNotification($user, 'task_created');
        $this->sendNotification($user, 'project_member_added');
        $read->markAsRead();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/notifications/read')
            ->assertOk()
            ->assertJsonPath('message', 'Read notifications deleted successfully.');

        $this->assertSame(1, $user->fresh()->notifications()->count());
        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());
    }

    public function test_delete_read_notifications_does_not_delete_other_users_read_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $read = $this->sendNotification($user, 'task_created');
        $otherRead = $this->sendNotification($otherUser, 'project_member_added');
        $read->markAsRead();
        $otherRead->markAsRead();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/notifications/read')
            ->assertOk();

        $this->assertSame(0, $user->fresh()->notifications()->count());
        $this->assertSame(1, $otherUser->fresh()->notifications()->count());
    }

    public function test_user_cannot_mutate_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = $this->sendNotification($owner, 'task_created');

        $this->actingAs($otherUser, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertNotFound();

        $this->assertSame(1, $owner->fresh()->unreadNotifications()->count());
    }

    public function test_notification_routes_require_authentication(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->getJson('/api/notifications/unread')->assertUnauthorized();
        $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
    }

    private function sendNotification(User $user, string $type): DatabaseNotification
    {
        $actor = User::factory()->create();

        $user->notify(new CollaborationDatabaseNotification(new NotificationData(
            type: $type,
            title: $type === 'task_created' ? 'Task created' : 'Added to project',
            message: 'Test notification message.',
            actorId: (int) $actor->id,
            targetUserId: (int) $user->id,
            entityType: 'project',
            entityId: 1,
        )));

        return $user->notifications()->firstOrFail();
    }
}
