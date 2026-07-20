<?php

namespace Tests\Feature;

use App\Ai\Agents\WorkspaceAssistant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class AskAiApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_ask_requires_authentication(): void
    {
        $this->postJson('/api/ai/ask', ['question' => 'What can you do?'])->assertUnauthorized();
    }

    public function test_ai_ask_validates_the_question(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ai/ask', ['question' => 'x'])
            ->assertUnprocessable()->assertJsonValidationErrors('question');
    }

    public function test_ai_ask_returns_the_faked_agent_answer(): void
    {
        WorkspaceAssistant::fake(['The workspace is ready.']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ai/ask', ['question' => 'Is my workspace ready?'])
            ->assertOk()->assertJsonPath('data.answer', 'The workspace is ready.');

        WorkspaceAssistant::assertPrompted('Is my workspace ready?');
    }

    public function test_ai_provider_failure_returns_service_unavailable_instead_of_server_error(): void
    {
        WorkspaceAssistant::fake(fn (): never => throw new RuntimeException('Provider unavailable'));
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ai/ask', ['question' => 'Please summarize my workspace.'])
            ->assertServiceUnavailable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'AI service is temporarily unavailable.');
    }
}
