<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::delete('logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
    ->name('password.forgot')
    ->middleware(['throttle:3,1', 'guest']);
Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

require __DIR__ . '/report.php';
