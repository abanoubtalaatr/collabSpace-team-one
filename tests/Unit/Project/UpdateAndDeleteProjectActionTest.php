<?php

namespace Tests\Unit\Project;

use App\Actions\Project\DeleteProjectAction;
use App\Actions\Project\UpdateProjectAction;
use App\DTOs\ProjectDTO;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\NotificationService;
use Mockery;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| UpdateProjectActionTest
|--------------------------------------------------------------------------
*/
class UpdateProjectActionTest extends TestCase
{
    protected $repoMock;

    protected UpdateProjectAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoMock = Mockery::mock(ProjectRepositoryInterface::class);
        $this->action = new UpdateProjectAction($this->repoMock, Mockery::mock(NotificationService::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_updates_project_with_new_data(): void
    {
        // Arrange
        $existingProject = new Project(['id' => 1, 'name' => 'Old Name']);

        $dto = new ProjectDTO(
            name: 'New Name',
            description: 'Updated desc',
            startDate: '2025-02-01',
            deadline: '2025-12-01',
            priority: 'medium',
            status: 'in_progress',
            createdBy: 1,
            mediaFiles: [],
            teamIds: [],
        );

        $updatedProject = new Project([
            'id' => 1,
            'name' => 'New Name',
            'description' => 'Updated desc',
            'status' => 'in_progress',
        ]);

        $this->repoMock
            ->shouldReceive('update')
            ->once()
            ->with($existingProject, [
                'name' => 'New Name',
                'description' => 'Updated desc',
                'start_date' => '2025-02-01',
                'deadline' => '2025-12-01',
                'priority' => 'medium',
                'status' => 'in_progress',
            ])
            ->andReturn($updatedProject);

        // Act
        $result = $this->action->execute($existingProject, $dto);

        // Assert
        $this->assertEquals('New Name', $result->name);
        $this->assertEquals('in_progress', $result->status);
    }

    /** @test */
    public function it_returns_project_instance_after_update(): void
    {
        $project = new Project(['id' => 2, 'name' => 'Project X']);
        $dto = new ProjectDTO(
            name: 'Project X Updated',
            description: null,
            startDate: null,
            deadline: null,
            priority: 'high',
            status: 'active',
            createdBy: 1,
            mediaFiles: [],
            teamIds: [],
        );

        $this->repoMock
            ->shouldReceive('update')
            ->once()
            ->andReturn(new Project(['id' => 2, 'name' => 'Project X Updated']));

        $result = $this->action->execute($project, $dto);

        $this->assertInstanceOf(Project::class, $result);
    }
}

/*
|--------------------------------------------------------------------------
| DeleteProjectActionTest
|--------------------------------------------------------------------------
*/
class DeleteProjectActionTest extends TestCase
{
    protected $repoMock;

    protected DeleteProjectAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoMock = Mockery::mock(ProjectRepositoryInterface::class);
        $this->action = new DeleteProjectAction($this->repoMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_deletes_project_via_repository(): void
    {
        // Arrange — بنعمل project mock عشان clearMediaCollection مش بتتصل بـ DB
        $project = Mockery::mock(Project::class)->makePartial();
        $project->shouldReceive('clearMediaCollection')
            ->once()
            ->with(Project::MEDIA_COLLECTION_ATTACHMENTS);

        $this->repoMock
            ->shouldReceive('delete')
            ->once()
            ->with($project);

        // Act
        $this->action->execute($project);

        // Assert — Mockery بيتحقق تلقائياً إن الـ methods اتصلت
        $this->assertTrue(true);
    }

    /** @test */
    public function it_clears_media_before_deleting(): void
    {
        // بنتأكد إن الترتيب صح: أول clearMedia وبعدين delete
        $callOrder = [];

        $project = Mockery::mock(Project::class)->makePartial();
        $project->shouldReceive('clearMediaCollection')
            ->once()
            ->withArgs(function ($collection) use (&$callOrder) {
                $callOrder[] = 'clearMedia';

                return true;
            });

        $this->repoMock
            ->shouldReceive('delete')
            ->once()
            ->withArgs(function ($p) use (&$callOrder) {
                $callOrder[] = 'delete';

                return true;
            });

        $this->action->execute($project);

        $this->assertEquals(['clearMedia', 'delete'], $callOrder);
    }
}
