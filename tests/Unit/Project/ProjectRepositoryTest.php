<?php

namespace Tests\Unit\Project;

use Tests\TestCase;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class ProjectRepositoryTest extends TestCase
{
    // RefreshDatabase بتعمل rollback بعد كل test — الـ DB بتفضل نظيفة
    use RefreshDatabase;

    protected ProjectRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProjectRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | create
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_creates_a_project_in_database(): void
    {
        // Arrange
        $user = User::factory()->create();
        $data = [
            'name'        => 'Repo Test Project',
            'description' => 'Testing the repo',
            'status'      => 'active',
            'priority'    => 'high',
            'created_by'  => $user->id,
        ];

        // Act
        $project = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Project::class, $project);
        $this->assertDatabaseHas('projects', [
            'name'       => 'Repo Test Project',
            'created_by' => $user->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | findById
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_finds_existing_project_by_id(): void
    {
        $user    = User::factory()->create();
        $created = Project::factory()->create(['created_by' => $user->id]);

        $found = $this->repository->findById($created->id);

        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
        $this->assertEquals($created->name, $found->name);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_project_id(): void
    {
        $result = $this->repository->findById(99999);

        $this->assertNull($result);
    }

    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_updates_project_data_correctly(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->create([
            'name'       => 'Old Name',
            'created_by' => $user->id,
        ]);

        $updated = $this->repository->update($project, [
            'name'   => 'New Name',
            'status' => 'completed',
        ]);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals('completed', $updated->status);
        $this->assertDatabaseHas('projects', [
            'id'   => $project->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function it_returns_refreshed_project_after_update(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);

        $result = $this->repository->update($project, ['name' => 'Refreshed Name']);

        // بنتأكد إن الـ refresh اشتغل — القيمة اتعدلت فعلاً
        $this->assertEquals('Refreshed Name', $result->name);
    }

    /*
    |--------------------------------------------------------------------------
    | delete
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_deletes_project_from_database(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id]);
        $id      = $project->id;

        $this->repository->delete($project);

        $this->assertDatabaseMissing('projects', ['id' => $id]);
    }

    /*
    |--------------------------------------------------------------------------
    | getAllPaginated
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(5)->create(['created_by' => $user->id]);

        $request = Request::create('/projects', 'GET');
        $result  = $this->repository->getAllPaginated($request, 3);

        // بنتأكد إن الـ pagination شغال
        $this->assertEquals(3, $result->perPage());
        $this->assertEquals(5, $result->total());
        $this->assertCount(3, $result->items());
    }

    /*
    |--------------------------------------------------------------------------
    | getByCreatorPaginated
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_returns_only_projects_by_specific_creator(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Project::factory()->count(3)->create(['created_by' => $userA->id]);
        Project::factory()->count(2)->create(['created_by' => $userB->id]);

        $request = Request::create('/projects', 'GET');
        $result  = $this->repository->getByCreatorPaginated($request, $userA->id);

        // بنتأكد إن بتيجي projects الـ userA بس
        $this->assertEquals(3, $result->total());
        foreach ($result->items() as $project) {
            $this->assertEquals($userA->id, $project->created_by);
        }
    }
}
