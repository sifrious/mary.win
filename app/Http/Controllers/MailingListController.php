<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeToMailingListRequest;
use App\Mail\MailingListConfirmationMail;
use App\Models\MailingListSubscription;
use App\Services\MailingList\MailingListSyncer;
use App\Services\MailingList\MailingListSyncException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Throwable;

class MailingListController extends Controller
{
    public function __construct(private readonly MailingListSyncer $syncer) {}

    /**
     * Record intent to subscribe and send a confirmation link.
     *
     * The local row is written before the email is attempted, so a mail-transport
     * outage never discards what the visitor submitted — and never reports a
     * subscription that has not actually been set in motion.
     */
    public function store(SubscribeToMailingListRequest $request): RedirectResponse
    {
        $email = $request->normalizedEmail();

        $subscription = MailingListSubscription::firstOrNew(['email' => $email]);

        // Already confirmed: repeated submission is a no-op. No second row, no
        // second confirmation email, no change to the original consent record.
        if ($subscription->exists && $subscription->isSubscribed()) {
            return $this->back('duplicate', 'That address is already subscribed. Nothing has changed, and the unsubscribe link in any of our emails still works.');
        }

        $resending = $subscription->exists && $subscription->isPending();

        $subscription->fill([
            'status' => MailingListSubscription::STATUS_PENDING,
            'source' => 'home',
            'consent_text' => (string) config('mailing-list.consent_text'),
            'consent_at' => Date::now(),
            'consent_ip' => $request->ip(),
            'unsubscribed_at' => null,
        ]);

        $confirmationToken = $subscription->issueConfirmationToken();

        $subscription->save();

        try {
            Mail::to($subscription->email)->send(
                new MailingListConfirmationMail($subscription, $confirmationToken)
            );
        } catch (Throwable $exception) {
            // The intent is saved and still pending; the visitor is told the
            // truth rather than being shown a success they did not get.
            Log::error('Mailing list confirmation email failed to send.', [
                'subscription_id' => $subscription->id,
                'exception' => $exception->getMessage(),
            ]);

            return $this->back('temporary_failure', 'We saved your request, but could not send the confirmation email just now. Please try again in a few minutes — you will not end up subscribed twice.');
        }

        return $resending
            ? $this->back('confirmation_resent', 'We sent another confirmation link to that address. Open it to finish subscribing.')
            : $this->back('success', 'Check your inbox. Open the confirmation link we just sent to finish subscribing.');
    }

    /**
     * Complete the confirmed opt-in.
     */
    public function confirm(string $token): View
    {
        $subscription = MailingListSubscription::query()
            ->where('confirmation_token_hash', MailingListSubscription::hashToken($token))
            ->first();

        if ($subscription === null || $subscription->confirmationHasExpired()) {
            return view('mailing-list.confirm', [
                'state' => 'invalid',
                'heading' => 'That confirmation link is no longer valid',
                'message' => 'Links expire after '.config('mailing-list.confirmation_ttl_hours').' hours. Sign up again and we will send a fresh one.',
                'unsubscribeUrl' => null,
            ]);
        }

        // Confirming twice is harmless: the row is already subscribed and the
        // token was cleared, so this branch is only reached once.
        $subscription->forceFill([
            'status' => MailingListSubscription::STATUS_SUBSCRIBED,
            'subscribed_at' => $subscription->subscribed_at ?? Date::now(),
            'unsubscribed_at' => null,
            'confirmation_token_hash' => null,
            'confirmation_expires_at' => null,
        ])->save();

        $this->mirrorToProvider($subscription, 'subscribe');

        return view('mailing-list.confirm', [
            'state' => 'confirmed',
            'heading' => 'You are subscribed',
            'message' => 'Thanks for confirming. Every email includes an unsubscribe link, and you can also use the one below at any time.',
            'unsubscribeUrl' => self::unsubscribeUrl($subscription),
        ]);
    }

    /**
     * Show the unsubscribe confirmation page. Opting out is a POST so that link
     * prefetching and mail scanners cannot unsubscribe somebody by accident.
     *
     * The link is a signed URL rather than a stored token: there is no extra
     * secret at rest, and every link is invalidated by rotating APP_KEY.
     */
    public function showUnsubscribe(MailingListSubscription $subscription): View
    {
        return view('mailing-list.unsubscribe', [
            'state' => $subscription->isUnsubscribed() ? 'already' : 'confirm',
            'subscription' => $subscription,
        ]);
    }

    public function unsubscribe(MailingListSubscription $subscription): View
    {
        if (! $subscription->isUnsubscribed()) {
            $subscription->forceFill([
                'status' => MailingListSubscription::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => Date::now(),
                'confirmation_token_hash' => null,
                'confirmation_expires_at' => null,
            ])->save();

            $this->mirrorToProvider($subscription, 'unsubscribe');
        }

        return view('mailing-list.unsubscribe', [
            'state' => 'done',
            'subscription' => $subscription,
        ]);
    }

    /**
     * Permanent, per-subscriber opt-out link for inclusion in outgoing mail.
     */
    public static function unsubscribeUrl(MailingListSubscription $subscription): string
    {
        return URL::signedRoute('mailing-list.unsubscribe.show', ['subscription' => $subscription]);
    }

    /**
     * Mirror the local state to the configured provider. The local table is the
     * system of record, so a provider outage is recorded for retry rather than
     * rolled back or hidden.
     */
    protected function mirrorToProvider(MailingListSubscription $subscription, string $operation): void
    {
        try {
            $operation === 'subscribe'
                ? $this->syncer->subscribe($subscription)
                : $this->syncer->unsubscribe($subscription);

            $subscription->forceFill([
                'provider_synced_at' => Date::now(),
                'provider_sync_failed_at' => null,
                'provider_sync_error' => null,
            ])->save();
        } catch (MailingListSyncException $exception) {
            Log::error('Mailing list provider sync failed.', [
                'subscription_id' => $subscription->id,
                'operation' => $operation,
                'exception' => $exception->getMessage(),
            ]);

            $subscription->forceFill([
                'provider_sync_failed_at' => Date::now(),
                'provider_sync_error' => $exception->getMessage(),
            ])->save();
        }
    }

    protected function back(string $state, string $message): RedirectResponse
    {
        return redirect()
            ->to(route('home').'#mailing-list')
            ->with('mailing_list', ['state' => $state, 'message' => $message]);
    }
}
