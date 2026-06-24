<?php
use Illuminate\Support\Facades\Route;

Route::get('test', function () {
    //
});

Route::get('container', function () {
    dd(app()->getBindings());
});

Route::get('routes', function () {
    return Route::getRoutes()->get();
});
