<?php

use App\Http\Controllers\Api\Meeting\MeetingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('meetings/calendar', [MeetingController::class, 'calendar'])->name('meetings.calendar');
    Route::get('meetings/upcoming', [MeetingController::class, 'upcoming'])->name('meetings.upcoming');
    Route::apiResource('meetings', MeetingController::class);
});
