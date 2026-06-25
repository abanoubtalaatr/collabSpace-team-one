<?php

use App\Http\Controllers\teams\TeamsController;
use Illuminate\Support\Facades\Route;

// , 'middleware' => ['role:admin']
Route::group(['prefix' => 'teams'], function () {
Route::apiResource('teams', TeamsController::class);
});

