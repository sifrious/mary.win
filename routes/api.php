<?php

use App\Http\Controllers\Games\FourLetterWordsController;
use App\Http\Controllers\SubscriberController;
use App\Http\Middleware\AuthenticateGameAccount;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Subscriber writes require their existing shared token. Game metadata and
| validation are anonymous. Saved-run routes require a verified mary.is token.
|
*/

Route::post('/subscribers', [SubscriberController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('api.subscribers.store');

Route::prefix('v1/four-letter-words')->middleware('throttle:30,1')->group(function () {
    Route::get('/metadata', [FourLetterWordsController::class, 'metadata']);
    Route::post('/validate-run', [FourLetterWordsController::class, 'validateRun']);
});

Route::prefix('v1/four-letter-words/runs')->middleware(['throttle:30,1', AuthenticateGameAccount::class])->group(function () {
    Route::put('/{run}', [FourLetterWordsController::class, 'store'])->whereUuid('run');
    Route::get('/{run}', [FourLetterWordsController::class, 'show'])->whereUuid('run');
});
