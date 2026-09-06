<?php

use App\Http\Controllers\SubscriberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Stateless, server-to-server endpoints. Callers authenticate with a shared
| token rather than a session, so there is no CSRF token to present.
|
*/

Route::post('/subscribers', [SubscriberController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('api.subscribers.store');
