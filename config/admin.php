<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Primary administrator (hardcoded portal login)
    |--------------------------------------------------------------------------
    |
    | Only this email + password_hash pair is accepted outside normal DB
    | credentials. The password is verified with password_verify() against
    | the bcrypt hash below (original password is not stored in the repo).
    |
    */

    'email' => 'ajay.melbourne@gmail.com',

    'password_hash' => '$2y$10$M1aF.oMljI8Tc2YN2rxXFu92mH4xwuXnQg9RK0n/Vj.edew1WOu0O',

    'default_name' => 'Ajay Melbourne',

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
