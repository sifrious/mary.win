<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * GitHub OAuth (Socialite), extracted out of route closures.
 *
 * Deliberately does NOT log token length, the raw GitHub payload, or the user's
 * email (the source did, on every login). Tokens are persisted via the User model's
 * `encrypted` casts, so they are never written to the database in plaintext.
 */
class GitHubAuthController extends Controller
{
    /** `repo` grants private-repo read access, which the reading feature needs. */
    private const SCOPES = ['repo', 'user:email', 'read:user'];

    /**
     * Send the user to GitHub to authorize.
     */
    public function redirect()
    {
        session()->forget('state');
        session()->save();

        return Socialite::driver('github')
            ->scopes(self::SCOPES)
            ->redirect();
    }

    /**
     * Handle the OAuth callback: create or log in the user, store tokens.
     */
    public function callback(): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'GitHub authentication failed. Please try again.');
        }

        $githubAttributes = [
            'github_id' => $githubUser->getId(),
            'github_token' => $githubUser->token,
            'github_refresh_token' => $githubUser->refreshToken,
        ];

        // Already signed in (an email/password account connecting GitHub, or a
        // re-auth for wider scopes): link GitHub to the CURRENT user. Matching by
        // email here would orphan their account whenever the GitHub email differs.
        if ($current = Auth::user()) {
            /** @var User $current */
            $current->update($githubAttributes);

            return $this->afterConnected('GitHub connected! You can now sync your repositories.');
        }

        // Otherwise match an existing account by GitHub id first, then verified email.
        $user = User::where('github_id', $githubUser->getId())->first();

        if (! $user && $githubUser->getEmail()) {
            $user = User::where('email', $githubUser->getEmail())->first();
        }

        if ($user) {
            // Always refresh the token so a re-auth with wider scopes takes effect.
            $user->update($githubAttributes);
            Auth::login($user);

            return $this->afterConnected('GitHub permissions updated! You can now access private repositories.');
        }

        $user = User::create([
            'name' => $githubUser->getName() ?? $githubUser->getNickname(),
            'email' => $githubUser->getEmail(),
            ...$githubAttributes,
        ]);

        Auth::login($user);

        return redirect()->route('kite.onboarding');
    }

    /**
     * Where to send the user once GitHub is connected: back to the page that
     * kicked off a re-auth (if one set a redirect), otherwise the kite dashboard.
     */
    private function afterConnected(string $reauthMessage): RedirectResponse
    {
        if ($redirect = session()->pull('reauth_redirect')) {
            return redirect($redirect)->with('success', $reauthMessage);
        }

        return redirect()->intended(route('kite.dashboard'));
    }

    /**
     * Re-authorize to upgrade granted scopes (e.g. after adding private-repo access).
     */
    public function reauth()
    {
        if (request('redirect')) {
            session(['reauth_redirect' => request('redirect')]);
        }

        session()->forget('state');
        session()->save();

        return Socialite::driver('github')
            ->scopes(self::SCOPES)
            ->with(['allow_signup' => 'true'])
            ->redirect();
    }
}
