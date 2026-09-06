<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\GitHubAuthController;
use App\Livewire\Actions\Logout;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
    Route::get('register', Register::class)->name('register');
    Route::get('forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');

    // GitHub OAuth entry point (guests signing in / signing up).
    Route::get('auth/github/redirect', [GitHubAuthController::class, 'redirect'])->name('github.redirect');
});

// The OAuth callback must be reachable by BOTH a guest signing in and an already
// authenticated user connecting GitHub or upgrading scopes, so it lives outside
// the guest/auth groups.
Route::get('auth/github/callback', [GitHubAuthController::class, 'callback'])->name('github.callback');

Route::middleware('auth')->group(function () {
    Route::get('verify-email', VerifyEmail::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::get('confirm-password', ConfirmPassword::class)
        ->name('password.confirm');

    // Re-authorize to upgrade GitHub scopes (e.g. grant private-repo access).
    Route::get('auth/github/reauth', [GitHubAuthController::class, 'reauth'])->name('github.reauth');
});

Route::post('logout', Logout::class)
    ->name('logout');
