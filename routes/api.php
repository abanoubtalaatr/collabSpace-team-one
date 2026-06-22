<?php

use App\Http\Controllers\Api\GlobalSearchController;
use Illuminate\Support\Facades\Route;

Route::get('search', GlobalSearchController::class);
