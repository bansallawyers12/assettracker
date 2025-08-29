<?php

return [
    'client_id' => env('GMAIL_CLIENT_ID'),
    'client_secret' => env('GMAIL_CLIENT_SECRET'),
    'refresh_token' => env('GMAIL_REFRESH_TOKEN'),
    'user_email' => env('GMAIL_USER_EMAIL'),
    'label' => env('GMAIL_LABEL', 'INBOX'),
    'enabled' => env('GMAIL_ENABLED', false),
    'batch_size' => env('GMAIL_BATCH_SIZE', 25),
    'users_per_tick' => env('GMAIL_USERS_PER_TICK', 3),
    'global_lock_seconds' => env('GMAIL_GLOBAL_LOCK_SECONDS', 55),
    'user_lock_seconds' => env('GMAIL_USER_LOCK_SECONDS', 300),
    'queue' => env('GMAIL_QUEUE', 'default'),
];


