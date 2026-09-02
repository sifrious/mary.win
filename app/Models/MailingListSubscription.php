<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class MailingListSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\MailingListSubscriptionFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    protected $guarded = [];

    protected $hidden = [
        'confirmation_token_hash',
        'consent_ip',
    ];

    protected function casts(): array
    {
        return [
            'consent_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'confirmation_expires_at' => 'datetime',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'provider_synced_at' => 'datetime',
            'provider_sync_failed_at' => 'datetime',
        ];
    }

    /**
     * Normalize an address so that casing and surrounding whitespace cannot
     * produce a second row for the same person.
     */
    public static function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSubscribed(): bool
    {
        return $this->status === self::STATUS_SUBSCRIBED;
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === self::STATUS_UNSUBSCRIBED;
    }

    public function confirmationHasExpired(): bool
    {
        return $this->confirmation_expires_at !== null
            && $this->confirmation_expires_at->isPast();
    }

    /**
     * Issue a fresh confirmation token, returning the plaintext value. Only the
     * digest is persisted, so the plaintext exists just long enough to be
     * placed in the outgoing email.
     */
    public function issueConfirmationToken(): string
    {
        $token = self::generateToken();

        $this->forceFill([
            'confirmation_token_hash' => self::hashToken($token),
            'confirmation_sent_at' => Date::now(),
            'confirmation_expires_at' => Date::now()->addHours(
                (int) config('mailing-list.confirmation_ttl_hours')
            ),
        ]);

        return $token;
    }
}
