<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BroadcastingAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_authentication_requires_a_user(): void
    {
        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-App.Models.User.1', 'socket_id' => '1234.5678',
        ])->assertUnauthorized();
    }

    public function test_broadcast_authentication_rejects_an_empty_body(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/broadcasting/auth')->assertUnprocessable()
            ->assertJsonValidationErrors(['channel_name', 'socket_id']);
    }

    public function test_broadcast_authentication_accepts_a_valid_body_with_null_driver(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "private-App.Models.User.{$user->id}", 'socket_id' => '1234.5678',
        ])->assertOk();
    }
}
