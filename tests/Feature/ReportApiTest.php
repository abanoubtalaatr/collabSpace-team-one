<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_report_routes_require_authentication(): void
    {
        foreach ([['GET', '/api/reports'], ['POST', '/api/reports'], ['GET', '/api/reports/projects'],
            ['GET', '/api/reports/tasks'], ['GET', '/api/reports/teams/1'], ['GET', '/api/reports/users/1']] as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized();
        }
    }

    public function test_non_admin_users_cannot_access_reports(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/reports')->assertForbidden();
    }

    public function test_admin_can_create_report_for_the_authenticated_account(): void
    {
        $admin = User::factory()->create();
        $admin->addRole(Role::factory()->create(['name' => 'admin']));
        Sanctum::actingAs($admin);

        $this->postJson('/api/reports', [
            'report_type' => 'project', 'start_date' => '2026-07-01',
            'end_date' => '2026-07-21', 'note' => 'Verified report',
        ])->assertCreated();

        $this->assertDatabaseHas(Report::class, [
            'user_id' => $admin->id, 'report_type' => 'project', 'note' => 'Verified report',
        ]);
    }

    public function test_report_creation_validates_type_and_date_range(): void
    {
        $admin = User::factory()->create();
        $admin->addRole(Role::factory()->create(['name' => 'admin']));
        Sanctum::actingAs($admin);

        $this->postJson('/api/reports', [
            'report_type' => 'invalid', 'start_date' => '2026-07-21', 'end_date' => '2026-07-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['report_type', 'end_date']);
    }
}
