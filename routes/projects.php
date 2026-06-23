<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\admin\ProjectController as AdminProjectController;
use App\Http\Controllers\api\project_manager\ProjectController as PMProjectController;
use App\Http\Controllers\api\team_member\ProjectController as TMProjectController;

Route::middleware(['auth:sanctum'])->group(function () {

    // ── Admin ────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('projects', AdminProjectController::class);
    });

    // ── Project Manager ──────────────────────────────────────
    Route::middleware('role:project_manager')->prefix('project-manager')->name('pm.')->group(function () {
        Route::apiResource('projects', PMProjectController::class);
    });

    // ── Team Member ──────────────────────────────────────────
    Route::middleware('role:team_member')->prefix('team-member')->name('tm.')->group(function () {
        Route::apiResource('projects', TMProjectController::class)->only(['index', 'show']);
    });

});
