<?php

use App\Http\Controllers\MailingListController;
use App\Models\MailingListSubscription;
use Illuminate\Support\Facades\URL;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('the signed unsubscribe link shows a confirmation step rather than acting on GET', function () {
    $subscription = MailingListSubscription::factory()->subscribed()->create(['email' => 'reader@example.com']);

    $response = $this->get(MailingListController::unsubscribeUrl($subscription));

    $response->assertOk();
    $response->assertSee('Unsubscribe from the email list?');
    $response->assertSee('reader@example.com');

    // Merely opening the link — as a mail scanner or prefetcher would — must
    // not remove anybody from the list.
    expect($subscription->refresh()->status)->toBe(MailingListSubscription::STATUS_SUBSCRIBED);
});

test('confirming the unsubscribe removes the address and mirrors it to the provider', function () {
    $syncer = fakeSyncer();

    $subscription = MailingListSubscription::factory()->subscribed()->create(['email' => 'reader@example.com']);

    $response = $this->post(URL::signedRoute('mailing-list.unsubscribe', ['subscription' => $subscription]));

    $response->assertOk();
    $response->assertSee('You have been unsubscribed');

    $subscription->refresh();

    expect($subscription->status)->toBe(MailingListSubscription::STATUS_UNSUBSCRIBED)
        ->and($subscription->unsubscribed_at)->not->toBeNull()
        ->and($syncer->unsubscribed)->toBe(['reader@example.com']);
});

test('unsubscribing twice is idempotent', function () {
    fakeSyncer();

    $subscription = MailingListSubscription::factory()->unsubscribed()->create();
    $unsubscribedAt = $subscription->unsubscribed_at;

    $response = $this->get(MailingListController::unsubscribeUrl($subscription));

    $response->assertOk();
    $response->assertSee('already unsubscribed');

    expect($subscription->refresh()->unsubscribed_at->timestamp)->toBe($unsubscribedAt->timestamp);
});

test('an unsigned or tampered unsubscribe link is refused', function () {
    $subscription = MailingListSubscription::factory()->subscribed()->create();

    $this->get(route('mailing-list.unsubscribe.show', ['subscription' => $subscription]))
        ->assertForbidden();

    $this->get(MailingListController::unsubscribeUrl($subscription).'x')
        ->assertForbidden();

    expect($subscription->refresh()->status)->toBe(MailingListSubscription::STATUS_SUBSCRIBED);
});
