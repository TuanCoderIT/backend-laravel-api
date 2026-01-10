<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Broadcasting Auth Route
| Override default broadcasting route to use auth:api middleware
|--------------------------------------------------------------------------
*/
Route::post('/broadcasting/auth', function () {
    return app(\Illuminate\Broadcasting\BroadcastController::class)->authenticate(request());
})->middleware('auth:api');

/*
|--------------------------------------------------------------------------
| Groups Test Route
|--------------------------------------------------------------------------
*/
Route::get('/groups/test', function () {
    return view('groups.test');
});