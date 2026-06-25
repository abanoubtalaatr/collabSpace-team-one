<?php

use App\Http\Controllers\teams\TeamsController;
use Illuminate\Support\Facades\Route;

// , 'middleware' => ['role:admin']
Route::group(['prefix' => 'teams'], function () {
    Route::get('/', [TeamsController::class, 'index'])->name('teams.index');
    Route::get('/create', [TeamsController::class, 'create'])->name('teams.create');
    Route::post('/store', [TeamsController::class, 'store'])->name('teams.store');
    Route::get('/{teamId}', [TeamsController::class, 'show'])->name('teams.show');
    Route::get('/{teamId}/edit', [TeamsController::class, 'edit'])->name('teams.edit');
    Route::put('/{teamId}/update', [TeamsController::class, 'update'])->name('teams.update');
    Route::delete('/{teamId}/delete', [TeamsController::class, 'delete'])->name('teams.delete');
});
