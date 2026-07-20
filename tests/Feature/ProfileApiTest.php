<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_bug_022_profile_name_update_persists_in_the_response_database_and_follow_up_get(): void
    {
        $user = User::factory()->create(['name' => 'Original Profile Name']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', [
            'name' => 'Updated Profile Name',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Profile Name');

        $this->assertSame('Updated Profile Name', $user->fresh()->name);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Profile Name');
    }
}
