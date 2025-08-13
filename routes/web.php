<?php

use Illuminate\Support\Facades\Route;
use MilenMk\LaravelEmailChangeConfirmation\Controllers\EmailChangeController;

/*
|--------------------------------------------------------------------------
| Email Change Confirmation Routes
|--------------------------------------------------------------------------
|
| These routes handle email change confirmation and denial requests.
| They are automatically registered by the service provider.
|
*/

$controllerClass = config('email-change-confirmation.email_change_controller', EmailChangeController::class);

Route::get('/confirm/{id}/{hash}', [$controllerClass, 'confirm'])
    ->name('confirm')
    ->where('id', '[0-9a-f-]+')
    ->where('hash', '[a-f0-9]+');

Route::get('/deny/{id}/{hash}', [$controllerClass, 'deny'])
    ->name('deny')
    ->where('id', '[0-9a-f-]+')
    ->where('hash', '[a-f0-9]+');

// Optional routes for manual integration
Route::post('/request', [$controllerClass, 'requestChange'])
    ->name('request')
    ->middleware(['auth', 'throttle:5,60']); // 5 requests per hour

Route::post('/cancel-pending', [$controllerClass, 'cancelPending'])
    ->name('cancel-pending')
    ->middleware(['auth', 'throttle:10,60']); // 10 requests per hour

Route::get('/pending', [$controllerClass, 'getPending'])
    ->name('pending')
    ->middleware(['auth', 'throttle:30,60']); // 30 requests per hour