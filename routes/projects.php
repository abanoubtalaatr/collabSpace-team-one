<?php

use App\Http\Controllers\Api\admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Api\Project\ProjectController as PMProjectController;
use App\Http\Controllers\Api\Project\ProjectGuestController;
use App\Http\Controllers\Api\Project\ProjectTaskController;
use App\Http\Controllers\Api\Project\ProjectTeamController;
use App\Http\Controllers\Api\Team\ProjectController as TMProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('projects/guests',[ProjectGuestController::class, 'index']);
    Route::get('projects/{project}/teams', [ProjectTeamController::class, 'index']);
    Route::post('projects/{project}/teams', [ProjectTeamController::class, 'store']);
    Route::delete('projects/{project}/teams', [ProjectTeamController::class, 'destroy']);
    Route::delete('projects/{project}/teams/{teamId}', [ProjectTeamController::class, 'removeOne']);

    Route::get('projects/{project}/tasks', [ProjectTaskController::class, 'index']);
    Route::post('projects/{project}/tasks', [ProjectTaskController::class, 'store']);

    // ── Admin ────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('projects', AdminProjectController::class);
    });

    Route::apiResource('projects', PMProjectController::class);

    // ── Team Member ──────────────────────────────────────────
    Route::middleware('role:member')->prefix('Team')->name('Team.')->group(function () {
        Route::apiResource('projects', TMProjectController::class)->only(['index', 'show']);
    });

});
