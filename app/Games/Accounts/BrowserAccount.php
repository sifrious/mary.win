<?php

namespace App\Games\Accounts;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Crypt;
use Sifrious\AccountsClient\Data\AccountReference;
use Sifrious\AccountsClient\Data\AppCredentials;
use Sifrious\AccountsClient\Data\LoginAttempt;
use Sifrious\AccountsClient\HostedAuthClient;
use Throwable;

final class BrowserAccount
{
    private function client(): HostedAuthClient
    {
        abort_unless(is_string(config('games.client_id')) && config('games.client_id') !== '', 503, 'Game sign-in is not configured yet.');
        abort_if(config('session.driver') === 'cookie', 503, 'Game sign-in requires server-side sessions.');

        return new HostedAuthClient(app(Factory::class), config('games.accounts_origin'), config('games.client_id'), config('games.callback_uri'), 'four-letter-words');
    }

    public function begin(Session $session): string
    {
        $client = $this->client();
        $attempt = $client->begin();
        $session->put('game_login_attempt', Crypt::encrypt($attempt));

        return $client->authorizationUrl($attempt);
    }

    public function complete(Session $session, string $callback): void
    {
        $encrypted = $session->pull('game_login_attempt');
        abort_unless(is_string($encrypted), 400, 'No pending game sign-in.');
        $attempt = Crypt::decrypt($encrypted);
        abort_unless($attempt instanceof LoginAttempt, 400);
        $client = $this->client();
        $credentials = $client->complete($callback, $attempt);
        $client->account($credentials);
        $session->regenerate();
        $session->put('game_credentials', Crypt::encrypt($credentials));
    }

    public function account(Session $session): ?AccountReference
    {
        $encrypted = $session->get('game_credentials');
        if (! is_string($encrypted)) {
            return null;
        }
        $credentials = Crypt::decrypt($encrypted);
        abort_unless($credentials instanceof AppCredentials, 401);
        $client = $this->client();
        if ($credentials->expiresAt <= time() + 30) {
            // A lost refresh response cannot safely be retried with the consumed token.
            $session->forget('game_credentials');
            $credentials = $client->refresh($credentials);
            $session->put('game_credentials', Crypt::encrypt($credentials));
        }

        return $client->account($credentials);
    }

    public function logout(Session $session): void
    {
        $encrypted = $session->pull('game_credentials');
        $session->forget('game_login_attempt');
        $session->regenerate();
        if (is_string($encrypted)) {
            try {
                $credentials = Crypt::decrypt($encrypted);
                if ($credentials instanceof AppCredentials) {
                    $this->client()->logout($credentials);
                }
            } catch (Throwable) {
                // Local credentials are cleared even when remote disconnection fails.
                $session->flash('game_notice', 'Signed out here. Remote disconnection could not be confirmed. Review connections at mary.is.');
            }
        }
    }
}
