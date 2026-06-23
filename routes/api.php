<?php
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;


// API routes for reports
Route::get('/reports', [ReportController::class, 'index']);
Route::post('/reports', [ReportController::class, 'store']);
Route::get('/reports/project-stats', [ReportController::class, 'getProjectReport']);
Route::get('/reports/projects', [ReportController::class, 'getProjectReport']);
Route::get('/reports/tasks', [ReportController::class, 'getTaskReport']);
Route::get('/reports/teams/{teamId}', [ReportController::class, 'getTeamReport']);
Route::get('/reports/users/{userId}', [ReportController::class, 'getUserReport']);
