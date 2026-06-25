<?php

namespace Tests\Unit\Project;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    protected ProjectService $service;

    protected $repoMock;

    protected function setUp(): void
    {
        parent::setUp();

        // بنعمل mock للـ Repository عشان نختبر الـ Service لوحده بدون DB
        $this->repoMock = Mockery::mock(ProjectRepositoryInterface::class);
        $this->service = new ProjectService($this->repoMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | getAllPaginated
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_returns_paginated_projects_for_all(): void
    {
        // Arrange
        $request = Request::create('/projects', 'GET');
        $paginator = $this->makeFakePaginator(3);

        $this->repoMock
            ->shouldReceive('getAllPaginated')
            ->once()
            ->with($request, 15)
            ->andReturn($paginator);

        // Act
        $result = $this->service->getAllPaginated($request);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(3, $result->items());
    }

    /*
    |--------------------------------------------------------------------------
    | getByCreatorPaginated
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_returns_paginated_projects_by_creator(): void
    {
        $request = Request::create('/projects', 'GET');
        $paginator = $this->makeFakePaginator(2);

        $this->repoMock
            ->shouldReceive('getByCreatorPaginated')
            ->once()
            ->with($request, 5, 15)
            ->andReturn($paginator);

        $result = $this->service->getByCreatorPaginated($request, 5);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
    }

    /*
    |--------------------------------------------------------------------------
    | getForTeamMemberPaginated
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_returns_paginated_projects_for_team_member(): void
    {
        $request = Request::create('/projects', 'GET');
        $paginator = $this->makeFakePaginator(1);

        $this->repoMock
            ->shouldReceive('getForTeamMemberPaginated')
            ->once()
            ->with($request, 7, 15)
            ->andReturn($paginator);

        $result = $this->service->getForTeamMemberPaginated($request, 7);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /*
    |--------------------------------------------------------------------------
    | findOrFail
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_returns_project_when_found(): void
    {
        // بنعمل project object وهمي بدون DB
        $project = new Project(['id' => 1, 'name' => 'Test Project']);

        $this->repoMock
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($project);

        $result = $this->service->findOrFail(1);

        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals('Test Project', $result->name);
    }

    /** @test */
    public function it_aborts_with_404_when_project_not_found(): void
    {
        $this->repoMock
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // بنتوقع إن Laravel هيرمي 404
        $this->expectException(HttpException::class);

        $this->service->findOrFail(999);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    /** بتعمل paginator وهمي للاختبار */
    private function makeFakePaginator(int $count): LengthAwarePaginator
    {
        $items = Project::factory()->count($count)->make();

        return new LengthAwarePaginator(
            $items,
            $count,
            15,
            1
        );
    }
}
