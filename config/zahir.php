<?php

return [
    'base_url' => env('ZAHIR_BASE_URL', 'http://localhost:8080'),
    'service_token' => env('ZAHIR_SERVICE_TOKEN'),

    // mary.win's own product identity. Holding another product's grant must
    // never open this site's authenticated area.
    'product' => 'mary-win',
    'access_entitlement' => 'access',

    'entitlement_decision_max_age_seconds' => (int) env('ZAHIR_ENTITLEMENT_DECISION_MAX_AGE_SECONDS', 30),

    'workos' => [
        'client_id' => env('WORKOS_CLIENT_ID'),
        'client_secret' => env('WORKOS_CLIENT_SECRET'),
        'issuer' => env('WORKOS_ISSUER', 'https://api.workos.com/'),
        // Exact-match allowlists. A trailing slash is a different URL.
        'callback_urls' => array_values(array_filter(explode(',', (string) env('WORKOS_CALLBACK_URLS', '')))),
        'post_logout_urls' => array_values(array_filter(explode(',', (string) env('WORKOS_POST_LOGOUT_URLS', '')))),
    ],
];
