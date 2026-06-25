<?php

use App\Http\Controllers\teams\TeamsController;
use Illuminate\Support\Facades\Route;

// , 'middleware' => ['role:admin']
Route::apiResource('teams', TeamsController::class);

