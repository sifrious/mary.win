<?php

return [
    'client_id' => env('GAMES_OAUTH_CLIENT_ID'),
    'callback_uri' => env('GAMES_OAUTH_CALLBACK_URI', 'https://mary.win/auth/mary/callback'),
    'accounts_origin' => env('GAMES_ACCOUNTS_ORIGIN', 'https://mary.is'),
];
