<?php

use App\Http\Controllers\Api\Team\TeamController;
use App\Http\Controllers\Api\Team\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('teams', TeamController::class);

    Route::get('teams/{team}/members', [TeamMemberController::class, 'index']);
    Route::post('teams/{team}/members', [TeamMemberController::class, 'store']);
    Route::delete('teams/{team}/members', [TeamMemberController::class, 'destroy']);
    Route::delete('teams/{team}/members/{userId}', [TeamMemberController::class, 'removeOne']);
});
