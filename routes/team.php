<?php

use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\teams\TeamsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('teams', TeamsController::class);
    Route::post('teams/{team}/members', [TeamMemberController::class, 'store']);
    Route::delete('teams/{team}/members/{user}', [TeamMemberController::class, 'destroy']);
});
