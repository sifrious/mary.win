<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

// The public site needs no account and must keep needing none.
Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
 * The authenticated area. `zahir.entitlement` re-asks Zahir on every request,
 * so a revoked grant closes access within one decision window rather than at
 * the end of a session. The `verified` middleware is gone: email verification
 * belongs to the identity provider now, and the local flag it checked no
 * longer means anything.
 */
Route::middleware(['auth', 'zahir.entitlement'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::redirect('settings', 'settings/profile');
    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
