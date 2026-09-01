<?php

namespace Tests\Concerns;

use Illuminate\Http\Client\Factory;
use Sifrious\AccountsClient\AccountsClient;
use Sifrious\AccountsClient\Laravel\RequireProductEntitlement;

/**
 * Answers the entitlement contract for tests that are about something else.
 *
 * Protected routes re-ask Zahir on every request, so a test about the profile
 * form still needs a decision. This supplies one without a network call, and
 * deliberately without touching the middleware itself — the gate under test in
 * every other file is the real one.
 */
trait FakesZahirEntitlement
{
    protected function allowProductAccess(bool $allowed = true, string $accountStatus = 'active'): void
    {
        $http = new Factory;
        $http->fake([
            '*/api/v1/entitlements/decide' => function (\Illuminate\Http\Client\Request $request) use ($http, $allowed, $accountStatus) {
                /** @var array<string, mixed> $data */
                $data = $request->data();

                return $http->response([
                    'allowed' => $allowed,
                    'account_id' => $data['account_id'] ?? '',
                    'account_status' => $accountStatus,
                    'product' => $data['product'] ?? '',
                    'entitlement' => $data['entitlement'] ?? '',
                    'evaluated_at' => gmdate('Y-m-d\TH:i:s\Z'),
                    'grant_id' => $allowed ? 'grant_test' : null,
                ]);
            },
        ]);

        $client = new AccountsClient($http, 'https://zahir.test', 'zhr.test.token');
        app()->instance(AccountsClient::class, $client);
        app()->instance(RequireProductEntitlement::class, new RequireProductEntitlement(
            $client,
            (string) config('zahir.product'),
            (string) config('zahir.access_entitlement'),
            (int) config('zahir.entitlement_decision_max_age_seconds'),
        ));
    }
}
