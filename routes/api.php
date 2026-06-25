<?php

use App\Http\Controllers\Api\GlobalSearchController;
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
Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->name('password.verify');
Route::post('resend-otp', [AuthController::class, 'resendOtp'])
    ->name('otp.resend')
    ->middleware(['throttle:3,1', 'guest']);
Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::get('search', GlobalSearchController::class)->middleware('auth:sanctum');

require __DIR__.'/report.php';
require __DIR__.'/team.php';
require __DIR__.'/projects.php';
require __DIR__.'/tasks.php';
