<?php

use App\Http\Controllers\Api\Profile\ProfileActivityController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Profile\ProfileFileController;
use App\Http\Controllers\Api\Profile\ProfileTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('show');
    Route::put('/', [ProfileController::class, 'update'])->name('update');
    Route::patch('/', [ProfileController::class, 'update']);

    Route::get('/activity', [ProfileActivityController::class, 'index'])->name('activity');

    Route::get('/tasks', [ProfileTaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/summary', [ProfileTaskController::class, 'summary'])->name('tasks.summary');

    Route::get('/files', [ProfileFileController::class, 'index'])->name('files.index');
    Route::post('/files', [ProfileFileController::class, 'store'])->name('files.store');
    Route::delete('/files/{fileId}', [ProfileFileController::class, 'destroy'])->name('files.destroy');
});
