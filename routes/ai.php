<?php

use App\Http\Controllers\Api\AskAiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:20,1'])->group(function () {
    Route::post('ai/ask', AskAiController::class)->name('ai.ask');
});
