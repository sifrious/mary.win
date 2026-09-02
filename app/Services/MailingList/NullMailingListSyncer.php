<?php

namespace App\Services\MailingList;

use App\Models\MailingListSubscription;

/**
 * Default syncer for the current product decision: no external provider is
 * configured, so the local table is the only store. Selecting a provider later
 * means adding an implementation and pointing MAILING_LIST_SYNCER at it — no
 * caller changes.
 */
class NullMailingListSyncer implements MailingListSyncer
{
    public function subscribe(MailingListSubscription $subscription): void
    {
        //
    }

    public function unsubscribe(MailingListSubscription $subscription): void
    {
        //
    }
}
