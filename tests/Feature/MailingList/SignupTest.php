<?php

use App\Mail\MailingListConfirmationMail;
use App\Models\MailingListSubscription;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * A payload as the rendered form would produce it: encrypted render timestamp,
 * empty honeypot, ticked consent box.
 */
function signupPayload(array $overrides = []): array
{
    return array_merge([
        'email' => 'reader@example.com',
        'consent' => '1',
        'form_rendered_at' => Crypt::encryptString((string) (time() - 30)),
        config('mailing-list.honeypot_field') => '',
    ], $overrides);
}

test('the home page renders the signup form without requiring javascript', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('action="'.route('mailing-list.store').'"', false);
    $response->assertSee('method="POST"', false);
    $response->assertSee('for="mailing-list-email"', false);
    $response->assertSee(config('mailing-list.consent_text'));
});

test('a valid submission records pending consent and sends a confirmation email', function () {
    Mail::fake();

    $response = $this->post(route('mailing-list.store'), signupPayload());

    $response->assertRedirect(route('home').'#mailing-list');
    $response->assertSessionHas('mailing_list.state', 'success');

    $subscription = MailingListSubscription::sole();

    expect($subscription->email)->toBe('reader@example.com')
        ->and($subscription->status)->toBe(MailingListSubscription::STATUS_PENDING)
        ->and($subscription->consent_at)->not->toBeNull()
        ->and($subscription->consent_text)->toBe(config('mailing-list.consent_text'))
        ->and($subscription->subscribed_at)->toBeNull();

    Mail::assertSent(MailingListConfirmationMail::class, fn ($mail) => $mail->hasTo('reader@example.com'));
});

test('the stored confirmation token is a digest, not the value emailed out', function () {
    Mail::fake();

    $this->post(route('mailing-list.store'), signupPayload());

    $plaintext = null;
    Mail::assertSent(MailingListConfirmationMail::class, function ($mail) use (&$plaintext) {
        $plaintext = $mail->confirmationToken;

        return true;
    });

    $stored = MailingListSubscription::sole()->confirmation_token_hash;

    expect($stored)->not->toBe($plaintext)
        ->and($stored)->toBe(hash('sha256', $plaintext));
});

test('an invalid email address is rejected with an accessible field error', function () {
    Mail::fake();

    $response = $this->from('/')->post(route('mailing-list.store'), signupPayload(['email' => 'not-an-address']));

    $response->assertSessionHasErrors('email');
    expect(MailingListSubscription::count())->toBe(0);
    Mail::assertNothingSent();
});

test('submitting without ticking the consent box is rejected', function () {
    Mail::fake();

    $response = $this->from('/')->post(route('mailing-list.store'), signupPayload(['consent' => null]));

    $response->assertSessionHasErrors('consent');
    expect(MailingListSubscription::count())->toBe(0);
});

test('resubmitting a confirmed address is idempotent and sends no second email', function () {
    Mail::fake();

    MailingListSubscription::factory()->subscribed()->create(['email' => 'reader@example.com']);

    $response = $this->post(route('mailing-list.store'), signupPayload());

    $response->assertSessionHas('mailing_list.state', 'duplicate');
    expect(MailingListSubscription::count())->toBe(1)
        ->and(MailingListSubscription::sole()->status)->toBe(MailingListSubscription::STATUS_SUBSCRIBED);

    Mail::assertNothingSent();
});

test('resubmitting a pending address resends the confirmation without creating a second row', function () {
    Mail::fake();

    MailingListSubscription::factory()->pendingWithToken('old-token')->create(['email' => 'reader@example.com']);

    $response = $this->post(route('mailing-list.store'), signupPayload());

    $response->assertSessionHas('mailing_list.state', 'confirmation_resent');
    expect(MailingListSubscription::count())->toBe(1)
        ->and(MailingListSubscription::sole()->confirmation_token_hash)
        ->not->toBe(MailingListSubscription::hashToken('old-token'));

    Mail::assertSentCount(1);
});

test('an address that previously unsubscribed can rejoin and is set back to pending', function () {
    Mail::fake();

    MailingListSubscription::factory()->unsubscribed()->create(['email' => 'reader@example.com']);

    $this->post(route('mailing-list.store'), signupPayload());

    $subscription = MailingListSubscription::sole();

    expect($subscription->status)->toBe(MailingListSubscription::STATUS_PENDING)
        ->and($subscription->unsubscribed_at)->toBeNull();
});

test('addresses differing only by case or whitespace do not create duplicate rows', function () {
    Mail::fake();

    $this->post(route('mailing-list.store'), signupPayload());
    $this->post(route('mailing-list.store'), signupPayload(['email' => '  Reader@Example.COM ']));

    expect(MailingListSubscription::count())->toBe(1);
});

test('a filled honeypot field is rejected without storing anything', function () {
    Mail::fake();

    $response = $this->from('/')->post(route('mailing-list.store'), signupPayload([
        config('mailing-list.honeypot_field') => 'https://spam.example',
    ]));

    $response->assertSessionHasErrors('email');
    expect(MailingListSubscription::count())->toBe(0);
    Mail::assertNothingSent();
});

test('a submission that arrives faster than a human could type is rejected', function () {
    Mail::fake();

    $response = $this->from('/')->post(route('mailing-list.store'), signupPayload([
        'form_rendered_at' => Crypt::encryptString((string) time()),
    ]));

    $response->assertSessionHasErrors('email');
    expect(MailingListSubscription::count())->toBe(0);
});

test('a forged or missing render timestamp is rejected', function () {
    Mail::fake();

    $this->from('/')->post(route('mailing-list.store'), signupPayload(['form_rendered_at' => 'forged']))
        ->assertSessionHasErrors('email');

    $this->from('/')->post(route('mailing-list.store'), signupPayload(['form_rendered_at' => null]))
        ->assertSessionHasErrors('email');

    expect(MailingListSubscription::count())->toBe(0);
});

test('a mail transport outage keeps the submitted intent and does not claim success', function () {
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('smtp unavailable'));

    $response = $this->post(route('mailing-list.store'), signupPayload());

    $response->assertSessionHas('mailing_list.state', 'temporary_failure');

    $subscription = MailingListSubscription::sole();

    expect($subscription->status)->toBe(MailingListSubscription::STATUS_PENDING)
        ->and($subscription->subscribed_at)->toBeNull();
});

test('the confirmation email renders in both html and plain text', function () {
    $subscription = MailingListSubscription::factory()->create(['email' => 'reader@example.com']);

    $mailable = new MailingListConfirmationMail($subscription, 'plaintext-token');
    $confirmUrl = route('mailing-list.confirm', ['token' => 'plaintext-token']);

    // Rendering is what caught a Markdown mailable declared with `view:`
    // instead of `markdown:`, which Mail::fake() cannot detect.
    $mailable->assertSeeInHtml($confirmUrl, escape: false);
    $mailable->assertSeeInText($confirmUrl);
    $mailable->assertSeeInText($subscription->consent_text);
});
