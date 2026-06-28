<?php

use App\Ai\Agents\WorkspaceAssistant;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Enums\Lab;

Route::get('test', function () {
    $response = (new WorkspaceAssistant())
        ->prompt(
            'get me last task compelted',
            // provider: Lab::Groq,
        );

    return $response;
});

Route::get('container', function () {
    dd(app()->getBindings());
});

Route::get('routes', function () {
    return Route::getRoutes()->get();
});
