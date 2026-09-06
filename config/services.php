<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Mailing-list ingest. The other sites (clever, …) POST signups to
    // /api/subscribers and authenticate with this shared token. Blank closes
    // the endpoint rather than opening it.
    'newsletter' => [
        'ingest_token' => env('NEWSLETTER_INGEST_TOKEN'),
    ],

    // GitHub OAuth (Socialite). Grants `repo` scope for private-repo reading, so the
    // access/refresh tokens are stored encrypted on the User model (see the `encrypted` casts).
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', rtrim((string) env('APP_URL'), '/') . '/auth/github/callback'),
    ],

    // AI code-analysis backend. Swappable between Langflow / LangChain / LangGraph
    // by changing the endpoint (and driver, when a backend needs bespoke handling).
    'analyzer' => [
        'driver' => env('AI_ANALYZER', 'null'), // null | langflow | langchain | langgraph
        'endpoint' => env('AI_ANALYZER_ENDPOINT'),
        'api_key' => env('AI_ANALYZER_API_KEY'),
        'timeout' => (int) env('AI_ANALYZER_TIMEOUT', 60),
    ],

];
