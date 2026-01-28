<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Validation
    |--------------------------------------------------------------------------
    |
    | Minimum password length for teachers and admin authentication.
    |
    */
    'password_min_length' => env('PASSWORD_MIN_LENGTH', 4),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Number of login attempts allowed before throttling.
    | Format: "attempts,decay_minutes"
    |
    */
    'login_throttle' => env('LOGIN_THROTTLE', '5,1'),
];
