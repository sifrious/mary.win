<?php

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => config(['services.newsletter.ingest_token' => 'test-token']));

function ingest(array $payload, ?string $token = 'test-token')
{
    return test()->postJson('/api/subscribers', $payload, $token === null ? [] : [
        'X-Ingest-Token' => $token,
    ]);
}

it('stores a signup sent with the ingest token', function () {
    ingest(['email' => 'reader@example.test', 'source' => 'clever'])
        ->assertCreated()
        ->assertJson(['status' => 'subscribed']);

    $subscriber = Subscriber::firstWhere('email', 'reader@example.test');

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->source)->toBe('clever')
        ->and($subscriber->isSubscribed())->toBeTrue();
});

it('lowercases and trims the address', function () {
    ingest(['email' => '  Reader@Example.test '])->assertCreated();

    expect(Subscriber::pluck('email')->all())->toBe(['reader@example.test']);
});

it('is idempotent, so signing up twice does not duplicate or fail', function () {
    ingest(['email' => 'reader@example.test', 'source' => 'clever'])->assertCreated();
    ingest(['email' => 'reader@example.test', 'source' => 'clever'])->assertOk();

    expect(Subscriber::count())->toBe(1);
});

it('revives an address that had unsubscribed', function () {
    Subscriber::create(['email' => 'reader@example.test', 'unsubscribed_at' => now()]);

    ingest(['email' => 'reader@example.test'])->assertOk();

    expect(Subscriber::first()->isSubscribed())->toBeTrue();
});

it('rejects a request with a wrong or missing token', function () {
    ingest(['email' => 'reader@example.test'], 'wrong-token')->assertUnauthorized();
    ingest(['email' => 'reader@example.test'], null)->assertUnauthorized();

    expect(Subscriber::count())->toBe(0);
});

it('fails closed when no ingest token is configured', function () {
    config(['services.newsletter.ingest_token' => null]);

    ingest(['email' => 'reader@example.test'], '')->assertUnauthorized();
    ingest(['email' => 'reader@example.test'], 'anything')->assertUnauthorized();

    expect(Subscriber::count())->toBe(0);
});

it('rejects an invalid address', function () {
    ingest(['email' => 'not-an-email'])->assertUnprocessable();

    expect(Subscriber::count())->toBe(0);
});
