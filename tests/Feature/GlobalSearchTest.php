<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_demo_seeder_creates_requested_record_counts(): void
    {
        $this->assertSame(100, User::query()->count());
        $this->assertSame(50, Project::query()->count());
        $this->assertSame(500, Task::query()->count());
        $this->assertSame(20, Team::query()->count());
        $this->assertSame(10, Role::query()->count());
    }

    public function test_it_searches_users_by_name(): void
    {
        $user = User::factory()->create([
            'name' => 'John Atlas Searcher',
            'email' => 'john.atlas.searcher@example.test',
        ]);

        $response = $this->getJson('/api/search?q=John%20Atlas');

        $response
            ->assertOk()
            ->assertJsonPath('query', 'John Atlas')
            ->assertJsonStructure([
                'query',
                'results' => [
                    [
                        'type',
                        'id',
                        'title',
                        'data',
                    ],
                ],
            ]);

        $this->assertSearchContains($response, 'User', $user->id, 'John Atlas Searcher');
        $this->assertSearchResultHasData($response, 'User', $user->id, 'name', 'John Atlas Searcher');
    }

    public function test_it_searches_users_by_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Email Lookup User',
            'email' => 'needle.email.lookup@example.test',
        ]);

        $response = $this->getJson('/api/search?q=needle.email.lookup');

        $response->assertOk();

        $this->assertSearchContains($response, 'User', $user->id, 'Email Lookup User');
        $this->assertSearchResultValue($response, 'User', $user->id, 'source.table', 'users');
        $this->assertSearchResultValue($response, 'User', $user->id, 'source.column', 'email');
    }

    public function test_it_can_search_users_only_by_type(): void
    {
        $user = User::factory()->create([
            'name' => 'Only User Search',
            'email' => 'only.user.search@example.test',
        ]);

        $response = $this->getJson('/api/search?q=Only%20User%20Search&type=user');

        $response->assertOk();
        $this->assertSearchContains($response, 'User', $user->id, 'Only User Search');
        $this->assertSearchContainsOnlyType($response, 'User');
    }

    public function test_it_can_search_by_specific_field(): void
    {
        $user = User::factory()->create([
            'name' => 'Field Search User',
            'email' => 'field-search-user@example.test',
        ]);

        $response = $this->getJson('/api/search?q=Field%20Search%20User&field=name');

        $response->assertOk();
        $this->assertSearchContains($response, 'User', $user->id, 'Field Search User');
    }

    public function test_it_searches_projects_by_name(): void
    {
        $project = Project::factory()->create([
            'name' => 'Apollo Knowledge Hub',
            'description' => 'A searchable internal collaboration project.',
        ]);

        $response = $this->getJson('/api/search?q=Apollo%20Knowledge');

        $response->assertOk();

        $this->assertSearchContains($response, 'Project', $project->id, 'Apollo Knowledge Hub');
    }

    public function test_it_searches_tasks_by_title(): void
    {
        $task = Task::factory()->create([
            'name' => 'Launch Checklist Search Task',
            'description' => 'A task record using the existing name column as its title.',
        ]);

        $response = $this->getJson('/api/search?q=Launch%20Checklist');

        $response->assertOk();

        $this->assertSearchContains($response, 'Task', $task->id, 'Launch Checklist Search Task');
    }

    public function test_it_searches_teams_by_name(): void
    {
        $team = Team::factory()->create([
            'name' => 'northstar-delivery-search-team',
            'display_name' => 'Northstar Delivery Search Team',
        ]);

        $response = $this->getJson('/api/search?q=northstar-delivery');

        $response->assertOk();

        $this->assertSearchContains($response, 'Team', $team->id, 'northstar-delivery-search-team');
    }

    public function test_it_searches_roles_by_name(): void
    {
        $role = Role::factory()->create([
            'name' => 'finance-reviewer-search-role',
            'display_name' => 'Finance Reviewer Search Role',
        ]);

        $user = User::factory()->create([
            'name' => 'Finance Role User',
            'email' => 'finance.role.user@example.test',
        ]);

        $role->users()->attach($user->id);

        $response = $this->getJson('/api/search?q=finance-reviewer');

        $response->assertOk();

        $this->assertSearchContains($response, 'Role', $role->id, 'finance-reviewer-search-role');
        $this->assertSearchResultHasData($response, 'Role', $role->id, 'users.0.id', $user->id);
    }

    public function test_it_returns_empty_results_for_no_matches(): void
    {
        $response = $this->getJson('/api/search?q=no-match-token-zzzz-9999');

        $response
            ->assertOk()
            ->assertJsonPath('query', 'no-match-token-zzzz-9999')
            ->assertJsonPath('results', []);
    }

    public function test_it_supports_partial_match_search(): void
    {
        $project = Project::factory()->create([
            'name' => 'Stellar Operations Workspace',
        ]);

        $response = $this->getJson('/api/search?q=ellar%20Opera');

        $response->assertOk();

        $this->assertSearchContains($response, 'Project', $project->id, 'Stellar Operations Workspace');
    }

    public function test_it_supports_case_insensitive_search(): void
    {
        $role = Role::factory()->create([
            'name' => 'CaseInsensitiveSearchLead',
            'display_name' => 'Case Insensitive Search Lead',
        ]);

        $response = $this->getJson('/api/search?q=caseinsensitivesearchlead');

        $response->assertOk();

        $this->assertSearchContains($response, 'Role', $role->id, 'CaseInsensitiveSearchLead');
    }

    private function assertSearchContains(
        TestResponse $response,
        string $type,
        int $id,
        string $title
    ): void {
        $results = collect($response->json('results'));

        $this->assertTrue(
            $results->contains(
                fn (array $result): bool => $result['type'] === $type
                    && $result['id'] === $id
                    && $result['title'] === $title
            ),
            "Failed asserting search results contain [{$type}] #{$id} titled [{$title}]."
        );
    }

    private function assertSearchResultHasData(
        TestResponse $response,
        string $type,
        int $id,
        string $path,
        mixed $expected
    ): void {
        $results = collect($response->json('results'));
        $result = $results->first(
            fn (array $item): bool => $item['type'] === $type && $item['id'] === $id
        );

        $this->assertNotNull($result, "Expected to find result [{$type}] #{$id}.");
        $this->assertSame($expected, data_get($result, "data.{$path}"));
    }

    private function assertSearchContainsOnlyType(TestResponse $response, string $type): void
    {
        $results = collect($response->json('results'));

        $this->assertNotEmpty($results, "Expected at least one result for type [{$type}].");
        $this->assertTrue(
            $results->every(fn (array $result): bool => $result['type'] === $type),
            "Failed asserting all search results are of type [{$type}]."
        );
    }

    private function assertSearchResultValue(
        TestResponse $response,
        string $type,
        int $id,
        string $path,
        mixed $expected
    ): void {
        $results = collect($response->json('results'));
        $result = $results->first(
            fn (array $item): bool => $item['type'] === $type && $item['id'] === $id
        );

        $this->assertNotNull($result, "Expected to find result [{$type}] #{$id}.");
        $this->assertSame($expected, data_get($result, $path));
    }
}
