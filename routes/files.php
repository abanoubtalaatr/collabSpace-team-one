<?php

use App\Http\Controllers\Api\File\FileController;
use App\Http\Controllers\Api\File\ProjectFileController;
use App\Http\Controllers\Api\File\TaskFileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('files', [FileController::class, 'index']);
    Route::post('files', [FileController::class, 'store']);
    Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('files/{file}', [FileController::class, 'show']);
    Route::post('files/{file}/attach', [FileController::class, 'attach']);
    Route::post('files/{file}/detach', [FileController::class, 'detach']);
    Route::delete('files/{file}', [FileController::class, 'destroy']);

    Route::get('projects/{project}/files', [ProjectFileController::class, 'index']);
    Route::post('projects/{project}/files', [ProjectFileController::class, 'store']);

    Route::get('tasks/{task}/files', [TaskFileController::class, 'index']);
    Route::post('tasks/{task}/files', [TaskFileController::class, 'store']);
});
