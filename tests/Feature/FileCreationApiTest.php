<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileCreationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_file_upload_routes_return_created(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $project = Project::factory()->createdBy($user)->create();
        $task = Task::factory()->for($project)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/files', ['file' => UploadedFile::fake()->create('detached.txt', 10, 'text/plain')])->assertCreated();
        $this->postJson("/api/projects/{$project->id}/files", ['file' => UploadedFile::fake()->create('project.pdf', 10, 'application/pdf')])->assertCreated();
        $this->postJson("/api/tasks/{$task->id}/files", ['file' => UploadedFile::fake()->create('task.txt', 10, 'text/plain')])->assertCreated();

        $this->assertSame(3, File::query()->count());
    }

    public function test_unauthorized_project_upload_does_not_create_an_orphan_record_or_disk_file(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $project = Project::factory()->createdBy($owner)->create();
        Sanctum::actingAs($outsider);

        $this->postJson("/api/projects/{$project->id}/files", [
            'file' => UploadedFile::fake()->create('forbidden.txt', 10, 'text/plain'),
        ])->assertForbidden();

        $this->assertDatabaseCount('files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
