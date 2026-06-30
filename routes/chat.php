<?php

use App\Http\Controllers\Api\Chat\ConversationController;
use App\Http\Controllers\Api\Chat\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::post('conversations/direct', [ConversationController::class, 'storeDirect']);
    Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
    Route::get('projects/{project}/conversation', [ConversationController::class, 'showProject']);

    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);
});
