<?php

namespace Tests\Conformance;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Sifrious\AccountsClient\AccountsClient;
use Sifrious\AccountsClient\Contracts\LoginDriver;
use Sifrious\AccountsClient\Laravel\RequireProductEntitlement;
use Sifrious\AccountsClient\Outcome\AuthenticationOutcome;
use Sifrious\AccountsClient\Testing\ConsumerUnderTest;
use Sifrious\AccountsClient\Testing\FakeIdentityProvider;
use Sifrious\AccountsClient\Testing\FakeZahir;
use Tests\TestCase;

/**
 * Drives mary.win's real routes, session, and users table for the shared suite.
 *
 * Wiring only — every assertion comes from the package. A third consumer on a
 * different Laravel major running the same eighteen cases is what turns
 * "reusable" from a claim into a measurement.
 */
final class MaryWinConsumer implements ConsumerUnderTest
{
    private FakeIdentityProvider $provider;

    private FakeZahir $zahir;

    public function __construct(private readonly TestCase $test)
    {
        $this->provider = new FakeIdentityProvider;
        $this->zahir = new FakeZahir;

        config([
            'zahir.workos.callback_urls' => ['http://localhost/auth/callback'],
            'zahir.workos.post_logout_urls' => ['http://localhost/'],
        ]);

        $client = new AccountsClient($this->zahir->httpFactory(), FakeZahir::BASE_URL, 'zhr.test.token');

        app()->instance(LoginDriver::class, $this->provider);
        app()->instance(AccountsClient::class, $client);
        app()->instance(RequireProductEntitlement::class, new RequireProductEntitlement(
            $client,
            $this->productKey(),
            (string) config('zahir.access_entitlement'),
            (int) config('zahir.entitlement_decision_max_age_seconds'),
        ));
    }

    public function provider(): FakeIdentityProvider
    {
        return $this->provider;
    }

    public function zahir(): FakeZahir
    {
        return $this->zahir;
    }

    public function productKey(): string
    {
        return (string) config('zahir.product');
    }

    public function beginLogin(): void
    {
        $this->test->get('/auth/redirect');
    }

    public function completeLogin(): AuthenticationOutcome
    {
        $response = $this->test->get('/auth/callback?code=code&state=state');
        $target = $response->headers->get('Location') ?? '';

        if (preg_match('#/auth/problem/([a-z_]+)#', $target, $matches) === 1) {
            return AuthenticationOutcome::from($matches[1]);
        }

        return AuthenticationOutcome::Authenticated;
    }

    public function projectionCount(string $accountId): int
    {
        return User::query()->where('zahir_account_id', $accountId)->count();
    }

    public function signedInAccountId(): ?string
    {
        $accountId = session('zahir.account_id');

        return Auth::check() && is_string($accountId) ? $accountId : null;
    }

    public function sessionPayload(): string
    {
        return json_encode(session()->all(), JSON_THROW_ON_ERROR);
    }

    public function signOut(): void
    {
        $this->test->post('/logout');
    }

    public function expireSession(): void
    {
        Auth::logout();
    }

    public function reachProtectedSurface(): bool
    {
        return $this->test->get('/dashboard')->getStatusCode() === 200;
    }

    /** The local display name is the only durable state this site owns yet. */
    public function durableStateFingerprint(): string
    {
        return User::query()->orderBy('id')->get(['zahir_account_id', 'name'])->toJson();
    }
}
