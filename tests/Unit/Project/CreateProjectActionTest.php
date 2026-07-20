<?php

namespace Tests\Unit\Project;

use App\Actions\Project\CreateProjectAction;
use App\DTOs\ProjectDTO;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateProjectActionTest extends TestCase
{
    use RefreshDatabase;

    protected $repoMock;

    protected CreateProjectAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoMock = Mockery::mock(ProjectRepositoryInterface::class);
        $this->action = new CreateProjectAction($this->repoMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_creates_project_without_media(): void
    {
        $creator = User::factory()->create();

        // Arrange — بنعمل DTO بدون files
        $dto = new ProjectDTO(
            name: 'My Project',
            description: 'Some description',
            startDate: '2025-01-01',
            deadline: '2025-06-01',
            priority: 'high',
            status: 'pending',
            type: null,
            createdBy: $creator->id,
            mediaFiles: [],   // مفيش files
            guestIds: [],
        );

        // الـ project اللي هيرجعه الـ repository
        $fakeProject = Project::factory()->create([
            'name' => 'My Project',
            'created_by' => $creator->id,
        ]);

        // بنقول للـ repository: لما تتصل بـ create ارجع fakeProject
        $this->repoMock
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'My Project',
                'description' => 'Some description',
                'start_date' => '2025-01-01',
                'deadline' => '2025-06-01',
                'priority' => 'high',
                'status' => 'pending',
                'type' => null,
                'created_by' => $creator->id,
            ])
            ->andReturn($fakeProject);

        // load('creator', 'media') بيتصل على العلاقات — بنتجاهله في Unit test
        // لو بيطلع error من load، استخدم RefreshDatabase بدل Mock

        // Act
        // لو عندك load في الـ action، ممكن تعمل partial mock أو تحول لـ Feature test
        // هنا بنفترض إن fakeProject->load() مش بيعمل DB call في الـ test
        $result = $this->action->execute($dto);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals('My Project', $result->name);
    }

    #[Test]
    public function it_passes_correct_data_to_repository(): void
    {
        $creator = User::factory()->create();
        $dto = new ProjectDTO(
            name: 'Sprint Project',
            description: null,
            startDate: null,
            deadline: null,
            priority: 'low',
            status: 'pending',
            type: null,
            createdBy: $creator->id,
            mediaFiles: [],
            guestIds: [],
        );

        // بنتأكد إن الـ data بتوصل للـ repository صح
        $this->repoMock
            ->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) use ($creator) {
                return $data['name'] === 'Sprint Project'
                    && $data['created_by'] === $creator->id
                    && $data['priority'] === 'low'
                    && $data['status'] === 'pending';
            })
            ->andReturn(Project::factory()->create([
                'name' => 'Sprint Project',
                'created_by' => $creator->id,
            ]));

        $this->action->execute($dto);

        // Mockery هيتأكد تلقائياً إن create اتصل مرة واحدة
        $this->assertTrue(true);
    }
}
