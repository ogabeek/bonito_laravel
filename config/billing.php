<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Period
    |--------------------------------------------------------------------------
    |
    | Defines the billing period boundaries.
    | Default: 26th of previous month to 25th of current month.
    |
    */
    'period_start_day' => env('BILLING_PERIOD_START_DAY', 26),
    'period_end_day' => env('BILLING_PERIOD_END_DAY', 25),
];
