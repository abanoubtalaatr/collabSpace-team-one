<?php

use App\Http\Controllers\Api\admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Api\Project\ProjectController as PMProjectController;
use App\Http\Controllers\Api\Project\ProjectTeamController;
use App\Http\Controllers\Api\Team\ProjectController as TMProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('projects/{project}/teams', [ProjectTeamController::class, 'index']);
    Route::post('projects/{project}/teams', [ProjectTeamController::class, 'store']);
    Route::delete('projects/{project}/teams', [ProjectTeamController::class, 'destroy']);
    Route::delete('projects/{project}/teams/{teamId}', [ProjectTeamController::class, 'removeOne']);

    // ── Admin ────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('projects', AdminProjectController::class);
    });

    // ── Project Manager ──────────────────────────────────────
    // Route::middleware('role:Project')->group(function () {
    //     Route::apiResource('projects', PMProjectController::class);
    // });

    Route::apiResource('projects', PMProjectController::class);
    // ── Team Member ──────────────────────────────────────────
    Route::middleware('role:Member')->prefix('Member')->name('Member.')->group(function () {
        Route::apiResource('projects', TMProjectController::class)->only(['index', 'show']);
    });

});
