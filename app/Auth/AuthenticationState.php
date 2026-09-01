<?php

namespace App\Auth;

use Sifrious\AccountsClient\Outcome\AuthenticationOutcome;

/**
 * mary.win's words for each shared outcome.
 *
 * The package owns the vocabulary and decides which outcomes are worth
 * retrying; this owns the sentences. They are deliberately shorter and plainer
 * than the other products' — this is a personal site with one person signing
 * into it, not a hosted product with a support queue.
 */
final readonly class AuthenticationState
{
    private function __construct(
        public AuthenticationOutcome $outcome,
        public string $heading,
        public string $explanation,
    ) {}

    public static function for(AuthenticationOutcome $outcome): self
    {
        return new self($outcome, ...match ($outcome) {
            AuthenticationOutcome::Canceled => ['Sign-in cancelled', 'Nothing was changed.'],
            AuthenticationOutcome::CallbackExpired => ['That link expired', 'Sign-in links are short-lived. Starting again takes a moment.'],
            AuthenticationOutcome::ReplayRejected => ['That link was already used', 'Each one works once.'],
            AuthenticationOutcome::CallbackInvalid => ['Could not verify that sign-in', 'The response did not pass our checks, so we stopped rather than trust it.'],
            AuthenticationOutcome::ProviderFailure => ['The sign-in service had a problem', 'Nothing is wrong with the account.'],
            AuthenticationOutcome::ZahirUnavailable => ['Cannot confirm access right now', 'The account service is unreachable. Access has not changed.'],
            AuthenticationOutcome::UnauthorizedProduct => ['This account cannot sign in here', 'It has no access to mary.win. Trying again will not change that.'],
            AuthenticationOutcome::Suspended => ['This account is suspended', 'It cannot start a session. Nothing has been deleted.'],
            AuthenticationOutcome::SessionExpired => ['Session expired', 'Signed out after a period of inactivity.'],
            AuthenticationOutcome::LoggedOut => ['Signed out', 'The session on this device has ended.'],
            AuthenticationOutcome::Authenticated => ['Signed in', 'You are signed in.'],
        });
    }

    /** Retryability is the package's judgement, not a second opinion kept here. */
    public function offersRetry(): bool
    {
        return $this->outcome->isRetryable();
    }
}
