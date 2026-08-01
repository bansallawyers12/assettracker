<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primary administrator (bootstrap login)
    |--------------------------------------------------------------------------
    |
    | ADMIN_EMAIL + ADMIN_PASSWORD_HASH are used only to create the primary
    | administrator on first login. After that user exists, the database
    | password is authoritative — the env hash is not a permanent backdoor.
    | Rotate the DB password via artisan/tinker if needed; the admin UI does
    | not reset the primary administrator password.
    |
    */

    'email' => env('ADMIN_EMAIL') ? strtolower(trim((string) env('ADMIN_EMAIL'))) : null,

    'password_hash' => env('ADMIN_PASSWORD_HASH') ? (string) env('ADMIN_PASSWORD_HASH') : null,

    'default_name' => env('ADMIN_DEFAULT_NAME', 'Administrator'),

    /*
    |--------------------------------------------------------------------------
    | 2FA grace logins
    |--------------------------------------------------------------------------
    |
    | Users without 2FA may complete this many full logins before they are
    | restricted to the 2FA setup flow only.
    |
    */

    'two_factor_grace_logins' => 3,

];
