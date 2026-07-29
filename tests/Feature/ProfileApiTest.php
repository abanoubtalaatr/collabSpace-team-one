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

        $this->put('/api/profile?name=Miro', [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Miro');

        $this->assertSame('Miro', $user->fresh()->name);

        $this->putJson('/api/profile', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profile']);

        $boundary = '----ProfileUpdateBoundary';
        $multipartBody = implode("\r\n", [
            "--{$boundary}",
            'Content-Disposition: form-data; name="name"',
            '',
            'hanan',
            "--{$boundary}",
            'Content-Disposition: form-data; name="job_title"',
            '',
            'Project Manager',
            "--{$boundary}",
            'Content-Disposition: form-data; name="experience_years"',
            '',
            '5',
            "--{$boundary}--",
            '',
        ]);

        $this->call(
            'PUT',
            '/api/profile',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => "multipart/form-data; boundary={$boundary}",
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_LENGTH' => (string) strlen($multipartBody),
            ],
            $multipartBody,
        )
            ->assertOk()
            ->assertJsonPath('data.name', 'hanan')
            ->assertJsonPath('data.job_title', 'Project Manager')
            ->assertJsonPath('data.experience_years', 5);

        $this->postJson('/api/profile', [
            'name' => 'Post Updated Name',
            'about' => 'Updated about',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Post Updated Name')
            ->assertJsonPath('data.about', 'Updated about');

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Post Updated Name');
    }
}
