<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\admin\ProjectController as AdminProjectController;
use App\Http\Controllers\api\project_manager\ProjectController as ProjectManagerProjectController;
use App\Http\Controllers\api\team_member\ProjectController as TeamMemberProjectController;



Route::prefix('admin')->middleware(['role:admin']) ->group(function () { 
    Route::apiResource('projects', AdminProjectController::class); 
    
});
Route::prefix('project_manager')->middleware(['role:admin']) ->group(function () { 
    Route::apiResource('projects', ProjectManagerProjectController::class); 
    
});

Route::prefix('team_member')->middleware(['role:team_member']) ->group(function () { 
    Route::apiResource('projects', TeamMemberProjectController::class); 
    
});

