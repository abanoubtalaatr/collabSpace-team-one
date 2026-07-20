<?php

namespace Tests\Unit\Project;

use App\Actions\Project\DeleteProjectAction;
use App\Actions\Project\UpdateProjectAction;
use App\DTOs\ProjectDTO;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateAndDeleteProjectActionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_updates_project_with_new_data(): void
    {
        $repository = Mockery::mock(ProjectRepositoryInterface::class);
        $action = new UpdateProjectAction($repository, Mockery::mock(NotificationService::class));
        $existingProject = Project::factory()->create(['name' => 'Old Name']);
        $dto = new ProjectDTO(
            name: 'New Name',
            description: 'Updated desc',
            startDate: '2025-02-01',
            deadline: '2025-12-01',
            priority: 'medium',
            status: 'in_progress',
            type: null,
            createdBy: $existingProject->created_by,
            mediaFiles: [],
            guestIds: [],
        );
        $updatedProject = Project::factory()->create([
            'name' => 'New Name',
            'description' => 'Updated desc',
            'status' => 'in_progress',
        ]);

        $repository
            ->shouldReceive('update')
            ->once()
            ->with($existingProject, [
                'name' => 'New Name',
                'description' => 'Updated desc',
                'start_date' => '2025-02-01',
                'deadline' => '2025-12-01',
                'priority' => 'medium',
                'status' => 'in_progress',
                'type' => null,
            ])
            ->andReturn($updatedProject);

        $result = $action->execute($existingProject, $dto);

        $this->assertSame('New Name', $result->name);
        $this->assertSame('in_progress', $result->status->value);
    }

    #[Test]
    public function it_returns_project_instance_after_update(): void
    {
        $repository = Mockery::mock(ProjectRepositoryInterface::class);
        $action = new UpdateProjectAction($repository, Mockery::mock(NotificationService::class));
        $project = Project::factory()->create(['name' => 'Project X']);
        $dto = new ProjectDTO(
            name: 'Project X Updated',
            description: null,
            startDate: null,
            deadline: null,
            priority: 'high',
            status: 'pending',
            type: null,
            createdBy: $project->created_by,
            mediaFiles: [],
            guestIds: [],
        );

        $repository
            ->shouldReceive('update')
            ->once()
            ->andReturn(Project::factory()->create(['name' => 'Project X Updated']));

        $this->assertInstanceOf(Project::class, $action->execute($project, $dto));
    }

    #[Test]
    public function it_deletes_project_via_repository(): void
    {
        $repository = Mockery::mock(ProjectRepositoryInterface::class);
        $action = new DeleteProjectAction($repository);
        $project = Mockery::mock(Project::class)->makePartial();
        $project->shouldReceive('clearMediaCollection')
            ->once()
            ->with(Project::MEDIA_COLLECTION_ATTACHMENTS);
        $repository->shouldReceive('delete')
            ->once()
            ->with($project);

        $action->execute($project);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_clears_media_before_deleting(): void
    {
        $repository = Mockery::mock(ProjectRepositoryInterface::class);
        $action = new DeleteProjectAction($repository);
        $callOrder = [];
        $project = Mockery::mock(Project::class)->makePartial();
        $project->shouldReceive('clearMediaCollection')
            ->once()
            ->withArgs(function (string $collection) use (&$callOrder): bool {
                $callOrder[] = 'clearMedia';

                return $collection === Project::MEDIA_COLLECTION_ATTACHMENTS;
            });
        $repository->shouldReceive('delete')
            ->once()
            ->withArgs(function (Project $deletedProject) use (&$callOrder, $project): bool {
                $callOrder[] = 'delete';

                return $deletedProject === $project;
            });

        $action->execute($project);

        $this->assertSame(['clearMedia', 'delete'], $callOrder);
    }
}
