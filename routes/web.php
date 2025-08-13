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

Route::get('confirm/{id}/{hash}', [$controllerClass, 'confirm'])
    ->name('confirm')
    ->where('id', '[0-9a-f-]+')
    ->where('hash', '[a-f0-9]+')
    ->middleware(['web', 'signed']);

Route::get('deny/{id}/{hash}', [$controllerClass, 'deny'])
    ->name('deny')
    ->where('id', '[0-9a-f-]+')
    ->where('hash', '[a-f0-9]+')
    ->middleware(['web', 'signed']);

// Optional routes for manual integration
Route::post('request', [$controllerClass, 'requestChange'])
    ->name('request')
    ->middleware('auth');

Route::post('cancel-pending', [$controllerClass, 'cancelPending'])
    ->name('cancel-pending')
    ->middleware('auth');

// Also add GET route for direct access (like from email links)
Route::get('cancel-pending', [$controllerClass, 'cancelPending'])
    ->name('cancel-pending-get')
    ->middleware('auth');
