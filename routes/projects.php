<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\admin\ProjectController as AdminProjectController;
use App\Http\Controllers\api\Project\ProjectController as PMProjectController;
use App\Http\Controllers\api\Team\ProjectController as TMProjectController;

Route::middleware(['auth:sanctum'])->group(function () {
 
    
    // ── Admin ────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('projects', AdminProjectController::class);
    });

    // ── Project Manager ──────────────────────────────────────
    Route::middleware('role:Project')->prefix('Project')->name('Project.')->group(function () {
        Route::apiResource('projects', PMProjectController::class);
    });

    // ── Team Member ──────────────────────────────────────────
    Route::middleware('role:Member')->prefix('Member')->name('Member.')->group(function () {
        Route::apiResource('projects', TMProjectController::class)->only(['index', 'show']);
    });

});
