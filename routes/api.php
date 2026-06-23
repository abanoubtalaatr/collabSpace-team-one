<?php
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\api\UserController;
use Illuminate\Support\Facades\Route;


// API routes for reports
Route::get('/reports', [ReportController::class, 'index']);
Route::post('/reports', [ReportController::class, 'store']);

// API routes for Project Reports
Route::get('/reports/projects', [ProjectController::class, 'getProjectReport']);
// API routes for task Reports
Route::get('/reports/tasks', [TaskController::class, 'getTaskReport']);
// API routes for team Reports
Route::get('/reports/teams/{teamId}', [TeamController::class, 'getTeamReport']);
// API routes for user Reports
Route::get('/reports/users/{userId}', [UserController::class, 'getUserReport']);
