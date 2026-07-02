<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\WorkspaceAssistant;
use App\Http\Controllers\Controller;
use App\Http\Requests\AskAiRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Enums\Lab;

class AskAiController extends Controller
{
    use ApiResponse;

    public function __invoke(AskAiRequest $request): JsonResponse
    {
        $response = WorkspaceAssistant::make($request->user())
            ->prompt(
                $request->validated('question'),
                provider: [Lab::Groq, Lab::Gemini],
            );

        return $this->apiResponse([
            'answer' => $response->text,
            'usage' => $response->usage->toArray(),
        ], 'AI response generated successfully');
    }
}
