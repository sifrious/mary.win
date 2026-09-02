<?php

namespace App\Services\MailingList;

use App\Models\MailingListSubscription;

/**
 * Narrow boundary to whatever email service provider mirrors this list.
 *
 * The local database remains the system of record. Implementations are expected
 * to throw MailingListSyncException on any transient or permanent provider
 * failure; callers record the failure and keep the local subscription.
 */
interface MailingListSyncer
{
    public function subscribe(MailingListSubscription $subscription): void;

    public function unsubscribe(MailingListSubscription $subscription): void;
}
