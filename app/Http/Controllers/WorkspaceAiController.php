<?php

namespace App\Http\Controllers;

use App\Ai\Agents\WorkspaceAssistant;
use Illuminate\Http\Request;
use Laravel\Ai\Enums\Lab;

class WorkspaceAiController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'prompt' => ['required', 'string', 'min:10', 'max:1000']
        ]);

        $response = (new WorkspaceAssistant())
            ->prompt(
                $request->prompt,
                provider: [Lab::Groq, Lab::Gemini],
            );

        return $response;
    }
}
