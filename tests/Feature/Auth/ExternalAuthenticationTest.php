<?php

use App\Models\User;
use Sifrious\AccountsClient\Contracts\LoginDriver;
use Sifrious\AccountsClient\Outcome\AuthenticationOutcome;
use Sifrious\AccountsClient\Testing\FakeIdentityProvider;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('the public site needs no account', function () {
    $this->get('/')->assertOk();
});

/*
 * The local-credential surfaces are gone, not hidden. A route that still
 * answered would be a second way in that nobody was maintaining.
 */
test('there is no registration, password reset, or verification route', function (string $path) {
    $this->get($path)->assertNotFound();
})->with([
    'register',
    'forgot-password',
    'reset-password/token',
    'confirm-password',
    'verify-email',
    'settings/password',
]);

test('the sign-in page offers the shared account and no password form', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Continue to sign in')
        ->assertDontSee('type="password"', false);
});

test('every problem state is announced and named', function () {
    foreach (AuthenticationOutcome::cases() as $outcome) {
        if ($outcome->grantsAccess()) {
            continue;
        }

        $this->get(route('auth.problem', ['state' => $outcome->value]))
            ->assertOk()
            ->assertSee('data-outcome="'.$outcome->value.'"', false)
            ->assertSee('role="alert"', false);
    }
});

test('an unrecognised problem state is not reflected back', function () {
    $this->get(route('auth.problem', ['state' => 'made-up']))
        ->assertOk()
        ->assertSee('data-outcome="callback_invalid"', false)
        ->assertDontSee('made-up');
});

test('signing out clears the session and keeps the projection', function () {
    config(['zahir.workos.post_logout_urls' => ['http://localhost/']]);
    app()->instance(LoginDriver::class, new FakeIdentityProvider);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['zahir.account_id' => $user->zahir_account_id])
        ->post('/logout')
        ->assertRedirect('http://localhost/');

    $this->assertGuest();
    expect(User::query()->count())->toBe(1);
});
