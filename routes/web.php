<?php

use App\Http\Controllers\MailingListController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
 * Email list signup. Plain server-rendered POST/GET so the whole flow works
 * with client-side JavaScript disabled.
 */
Route::post('mailing-list', [MailingListController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('mailing-list.store');

Route::get('mailing-list/confirm/{token}', [MailingListController::class, 'confirm'])
    ->middleware('throttle:30,1')
    ->name('mailing-list.confirm');

Route::get('mailing-list/unsubscribe/{subscription}', [MailingListController::class, 'showUnsubscribe'])
    ->middleware('signed')
    ->name('mailing-list.unsubscribe.show');

Route::post('mailing-list/unsubscribe/{subscription}', [MailingListController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('mailing-list.unsubscribe');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
