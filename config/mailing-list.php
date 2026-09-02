<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System of record
    |--------------------------------------------------------------------------
    |
    | The local `mailing_list_subscriptions` table is the system of record for
    | this list. Any external email service provider is a downstream mirror
    | kept in sync through the MailingListSyncer contract, never the source
    | of truth. This keeps consent evidence inside the application even when
    | the provider is unavailable or is later replaced.
    |
    */

    'syncer' => env('MAILING_LIST_SYNCER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Confirmed opt-in
    |--------------------------------------------------------------------------
    |
    | Signup uses confirmed (double) opt-in: a submission records intent as
    | `pending` and only becomes `subscribed` after the address owner follows
    | the emailed confirmation link. This prevents a visitor from subscribing
    | somebody else's address and gives defensible consent evidence.
    |
    */

    'confirmation_ttl_hours' => (int) env('MAILING_LIST_CONFIRMATION_TTL_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Bot resistance
    |--------------------------------------------------------------------------
    |
    | The form carries a honeypot field and a signed render timestamp. Both work
    | without client-side JavaScript. `min_fill_seconds` is the shortest time a
    | human plausibly takes to complete the form; faster submissions are
    | rejected. Set to 0 to disable the timing check.
    |
    */

    'honeypot_field' => env('MAILING_LIST_HONEYPOT_FIELD', 'website_url'),

    'min_fill_seconds' => (int) env('MAILING_LIST_MIN_FILL_SECONDS', 2),

    /*
    |--------------------------------------------------------------------------
    | Consent copy
    |--------------------------------------------------------------------------
    |
    | The exact sentence shown beside the submit control. It is stored verbatim
    | on each subscription so we can always show what a person agreed to, even
    | after the wording on the site changes.
    |
    */

    'consent_text' => 'I agree to receive occasional email from Mary Perry. No sharing, no selling, unsubscribe any time.',

    'privacy_url' => env('MAILING_LIST_PRIVACY_URL', '/privacy'),

];
