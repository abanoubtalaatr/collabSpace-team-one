<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileFileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_profile_file_008_delete_file_route_removes_the_owned_media_record_and_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $media = $user
            ->addMediaFromString('profile file contents')
            ->usingFileName('owned-file.txt')
            ->toMediaCollection(User::MEDIA_COLLECTION_FILES);
        $relativePath = $media->getPathRelativeToRoot();
        Sanctum::actingAs($user);

        Storage::disk('public')->assertExists($relativePath);

        $this->deleteJson("/api/profile/files/{$media->id}")
            ->assertOk()
            ->assertJsonPath('message', 'File deleted successfully.');

        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($relativePath);
    }

    public function test_api_profile_task_011_upload_file_route_creates_owned_profile_media(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/files', [
            'file' => UploadedFile::fake()->create(
                'requirements.pdf',
                100,
                'application/pdf',
            ),
        ])
            ->assertCreated()
            ->assertJsonPath('data.file_name', 'requirements.pdf');

        $media = $user->fresh()->getFirstMedia(User::MEDIA_COLLECTION_FILES);

        $this->assertNotNull($media);
        $this->assertSame($media->id, $response->json('data.id'));
        $this->assertSame($media->mime_type, $response->json('data.mime_type'));
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    }
}
