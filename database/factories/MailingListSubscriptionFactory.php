<?php

namespace Database\Factories;

use App\Models\MailingListSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailingListSubscription>
 */
class MailingListSubscriptionFactory extends Factory
{
    protected $model = MailingListSubscription::class;

    public function definition(): array
    {
        return [
            'email' => MailingListSubscription::normalizeEmail($this->faker->unique()->safeEmail()),
            'status' => MailingListSubscription::STATUS_PENDING,
            'source' => 'home',
            'consent_text' => (string) config('mailing-list.consent_text'),
            'consent_at' => now(),
            'consent_ip' => '127.0.0.1',
            'confirmation_token_hash' => null,
            'confirmation_sent_at' => null,
            'confirmation_expires_at' => null,
        ];
    }

    /**
     * A pending subscription holding a known confirmation token, so tests can
     * exercise the confirmation route without reading the outgoing email.
     */
    public function pendingWithToken(string $token): static
    {
        return $this->state(fn (): array => [
            'status' => MailingListSubscription::STATUS_PENDING,
            'confirmation_token_hash' => MailingListSubscription::hashToken($token),
            'confirmation_sent_at' => now(),
            'confirmation_expires_at' => now()->addHours((int) config('mailing-list.confirmation_ttl_hours')),
        ]);
    }

    public function subscribed(): static
    {
        return $this->state(fn (): array => [
            'status' => MailingListSubscription::STATUS_SUBSCRIBED,
            'subscribed_at' => now(),
            'confirmation_token_hash' => null,
            'confirmation_expires_at' => null,
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (): array => [
            'status' => MailingListSubscription::STATUS_UNSUBSCRIBED,
            'subscribed_at' => now()->subDay(),
            'unsubscribed_at' => now(),
        ]);
    }
}
