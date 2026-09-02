<?php

use App\Models\MailingListSubscription;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a valid confirmation link completes the opt-in and mirrors it to the provider', function () {
    $syncer = fakeSyncer();

    $subscription = MailingListSubscription::factory()
        ->pendingWithToken('valid-token')
        ->create(['email' => 'reader@example.com']);

    $response = $this->get(route('mailing-list.confirm', ['token' => 'valid-token']));

    $response->assertOk();
    $response->assertSee('You are subscribed');

    $subscription->refresh();

    expect($subscription->status)->toBe(MailingListSubscription::STATUS_SUBSCRIBED)
        ->and($subscription->subscribed_at)->not->toBeNull()
        ->and($subscription->confirmation_token_hash)->toBeNull()
        ->and($subscription->provider_synced_at)->not->toBeNull()
        ->and($syncer->subscribed)->toBe(['reader@example.com']);
});

test('a provider outage during confirmation still subscribes locally and records the failure', function () {
    fakeSyncer(failing: true);

    $subscription = MailingListSubscription::factory()
        ->pendingWithToken('valid-token')
        ->create(['email' => 'reader@example.com']);

    $response = $this->get(route('mailing-list.confirm', ['token' => 'valid-token']));

    $response->assertOk();

    $subscription->refresh();

    // The local table is the system of record: the person is subscribed, and
    // the unsynced provider state is left visible for retry rather than lost.
    expect($subscription->status)->toBe(MailingListSubscription::STATUS_SUBSCRIBED)
        ->and($subscription->provider_synced_at)->toBeNull()
        ->and($subscription->provider_sync_failed_at)->not->toBeNull()
        ->and($subscription->provider_sync_error)->toBe('provider unavailable');
});

test('an expired confirmation link is refused and leaves the subscription pending', function () {
    fakeSyncer();

    $subscription = MailingListSubscription::factory()
        ->pendingWithToken('stale-token')
        ->create([
            'email' => 'reader@example.com',
            'confirmation_expires_at' => now()->subHour(),
        ]);

    $response = $this->get(route('mailing-list.confirm', ['token' => 'stale-token']));

    $response->assertOk();
    $response->assertSee('no longer valid');

    expect($subscription->refresh()->status)->toBe(MailingListSubscription::STATUS_PENDING);
});

test('an unknown confirmation token is refused', function () {
    fakeSyncer();

    $response = $this->get(route('mailing-list.confirm', ['token' => 'never-issued']));

    $response->assertOk();
    $response->assertSee('no longer valid');
});
