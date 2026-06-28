<?php

use App\Http\Controllers\Api\Meeting\MeetingController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('meetings/calendar', [MeetingController::class, 'calendar'])->name('meetings.calendar');
    Route::get('meetings/upcoming', [MeetingController::class, 'upcoming'])->name('meetings.upcoming');
    Route::apiResource('meetings', MeetingController::class);

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});
