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

    'email' => strtolower(trim((string) env('ADMIN_EMAIL', 'ajay.melbourne@gmail.com'))),

    'password_hash' => (string) env(
        'ADMIN_PASSWORD_HASH',
        '$2y$10$M1aF.oMljI8Tc2YN2rxXFu92mH4xwuXnQg9RK0n/Vj.edew1WOu0O'
    ),

    'default_name' => env('ADMIN_DEFAULT_NAME', 'Ajay Melbourne'),

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
