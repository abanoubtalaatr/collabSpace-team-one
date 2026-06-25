<?php

use App\Http\Controllers\teams\TeamsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('teams', TeamsController::class);
});

