<?php

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread', [NotificationController::class, 'unread']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/read', [NotificationController::class, 'destroyRead']);
    Route::get('/{notification}', [NotificationController::class, 'show']);
    Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/{notification}', [NotificationController::class, 'destroy']);
});
