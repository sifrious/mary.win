<?php

use App\Http\Controllers\Auth\ZahirAuthController;
use Illuminate\Support\Facades\Route;

/*
 * Authentication is external. There is no registration, password reset, or
 * email verification route here: an account is resolved from a verified
 * external identity, and the provider owns credentials, verification, and
 * recovery. See the accounts-client ownership doc.
 */
Route::middleware('guest')->group(function () {
    // Named `login` because the framework redirects guests here.
    Route::view('login', 'auth.sign-in')->name('login');
    Route::get('auth/redirect', [ZahirAuthController::class, 'redirect'])->name('auth.redirect');
    Route::get('auth/callback', [ZahirAuthController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('auth.callback');
});

// Reachable signed in or out: a lapsed session lands here too.
Route::get('auth/problem/{state}', [ZahirAuthController::class, 'problem'])->name('auth.problem');

Route::post('logout', [ZahirAuthController::class, 'destroy'])->middleware('auth')->name('logout');
