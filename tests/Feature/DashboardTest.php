<?php

use App\Models\User;
use Tests\Concerns\FakesZahirEntitlement;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, FakesZahirEntitlement::class);

test('guests are sent to the sign-in page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('a lapsed session is told why rather than just bounced', function () {
    $this->withSession(['zahir.account_id' => 'acc_01test'])
        ->get('/dashboard')
        ->assertRedirect(route('auth.problem', ['state' => 'session_expired']));
});

test('an entitled account reaches the dashboard', function () {
    $this->allowProductAccess();

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk();
});

test('an account without the mary.win entitlement is refused', function () {
    $this->allowProductAccess(allowed: false);

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertRedirect(route('auth.problem', ['state' => 'unauthorized_product']));
});

test('a suspended account is refused as suspended', function () {
    $this->allowProductAccess(allowed: true, accountStatus: 'suspended');

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertRedirect(route('auth.problem', ['state' => 'suspended']));
});
