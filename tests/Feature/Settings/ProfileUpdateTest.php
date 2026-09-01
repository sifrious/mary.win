<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use Livewire\Livewire;
use Tests\Concerns\FakesZahirEntitlement;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, FakesZahirEntitlement::class);

test('profile page is displayed', function () {
    $this->allowProductAccess();

    $this->actingAs(User::factory()->create())
        ->get('/settings/profile')
        ->assertOk();
});

test('the display name can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->name)->toEqual('Test User');
});

test('the email address is not editable here', function () {
    $user = User::factory()->create(['email' => 'from-the-provider@example.test']);

    $this->actingAs($user);

    // The component exposes no writable email property at all. Editing it would
    // be reverted on the next sign-in, when the claim is refreshed.
    Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->email)->toEqual('from-the-provider@example.test');
});

test('the settings page shows the account the person signed in with', function () {
    $this->allowProductAccess();
    $user = User::factory()->create(['email' => 'person@example.test']);

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertOk()
        ->assertSee('person@example.test')
        ->assertSee($user->zahir_account_id)
        ->assertSee('managed by your shared account', false);
});
