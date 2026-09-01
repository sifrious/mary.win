<?php

namespace App\Http\Controllers\Auth;

use App\Auth\AuthenticationState;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Sifrious\AccountsClient\Outcome\AuthenticationOutcome;
use Sifrious\AccountsClient\ProductAuthenticator;

/**
 * Sign-in for the authenticated area of mary.win.
 *
 * The public site — portfolio, writing, contact — is served without any account
 * at all. Only the dashboard and settings sit behind this.
 *
 * There is no registration path on purpose. Under shared authentication an
 * account is not created by filling in a form here; it is resolved from a
 * verified external identity, and access still requires a Zahir grant.
 */
class ZahirAuthController extends Controller
{
    public function redirect(Request $request, ProductAuthenticator $authenticator): RedirectResponse
    {
        $request->query->set('redirect_uri', (string) config('zahir.workos.callback_urls.0'));

        return $authenticator->begin($request);
    }

    public function callback(Request $request, ProductAuthenticator $authenticator): RedirectResponse
    {
        $result = $authenticator->complete($request);

        if (! $result->grantsAccess()) {
            $this->record($result->outcome->value, $result->accountId());

            return redirect()->route('auth.problem', ['state' => $result->outcome->value]);
        }

        $accountId = (string) $result->accountId();

        // Keyed on the stable account ID, so a returning sign-in lands on the
        // record that already exists. Name and email are refreshed as display
        // metadata; neither is identity, and no credential is written.
        $user = User::query()->updateOrCreate(
            ['zahir_account_id' => $accountId],
            [
                'name' => $result->identity?->claims['name'] ?? $accountId,
                'email' => $result->identity?->claims['email'] ?? $accountId.'@accounts.invalid',
                'email_verified_at' => ($result->identity?->claims['email_verified'] ?? false) ? now() : null,
            ],
        );

        Auth::login($user, remember: true);

        // The callback is reached as a guest, so the pre-login session ID is
        // influenceable; regenerating is what closes session fixation.
        $request->session()->regenerate();
        $request->session()->put('zahir.account_id', $accountId);

        $this->record('authenticated', $accountId);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request, ProductAuthenticator $authenticator): RedirectResponse
    {
        $accountId = $request->session()->get('zahir.account_id');
        $logout = $authenticator->logout($request, (string) config('zahir.workos.post_logout_urls.0'));

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->record($logout->outcome->value, is_string($accountId) ? $accountId : null);

        return $logout->response;
    }

    public function problem(Request $request, string $state): View
    {
        // An unrecognised state is treated as a malformed callback rather than
        // reflected into the page.
        $outcome = AuthenticationOutcome::tryFrom($state) ?? AuthenticationOutcome::CallbackInvalid;

        return view('auth.problem', ['state' => AuthenticationState::for($outcome)]);
    }

    /** Outcome and opaque account only — never a subject, an email, or a token. */
    private function record(string $outcome, ?string $accountId): void
    {
        Log::info('marywin.authentication', [
            'outcome' => $outcome,
            'account_id' => $accountId,
            'product' => config('zahir.product'),
            'metric_count' => 1,
        ]);
    }
}
