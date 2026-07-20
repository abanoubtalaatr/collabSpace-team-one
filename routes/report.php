<?php

use App\Http\Controllers\Api\Report\ProjectController;
use App\Http\Controllers\Api\Report\ReportController;
use App\Http\Controllers\Api\Report\TaskController;
use App\Http\Controllers\Api\Report\TeamController;
use App\Http\Controllers\Api\Report\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports/projects', [ProjectController::class, 'getProjectReport']);
    Route::get('/reports/tasks', [TaskController::class, 'getTaskReport']);
    Route::get('/reports/teams/{teamId}', [TeamController::class, 'getTeamReport']);
    Route::get('/reports/users/{userId}', [UserController::class, 'getUserReport']);
});
