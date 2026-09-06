<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    /**
     * Ingest a mailing-list signup from one of the other sites.
     *
     * The caller is a server, not a browser: it authenticates with the shared
     * ingest token rather than a session, so this route is stateless and CSRF
     * does not apply.
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->hasValidToken($request)) {
            return response()->json(['message' => 'Invalid ingest token.'], 401);
        }

        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        // Signing up again is idempotent, and it revives an address that had
        // previously unsubscribed — the person just asked for mail again.
        $subscriber = Subscriber::updateOrCreate(
            ['email' => strtolower(trim($data['email']))],
            ['source' => $data['source'] ?? null, 'unsubscribed_at' => null],
        );

        return response()->json(
            ['status' => 'subscribed'],
            $subscriber->wasRecentlyCreated ? 201 : 200,
        );
    }

    /**
     * Compare the caller's token with the configured one in constant time.
     * A blank configured token fails closed: the endpoint is shut, not open.
     */
    private function hasValidToken(Request $request): bool
    {
        $expected = (string) config('services.newsletter.ingest_token');
        $provided = (string) $request->header('X-Ingest-Token', '');

        return $expected !== '' && hash_equals($expected, $provided);
    }
}
