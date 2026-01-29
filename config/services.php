<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'google' => [
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    ],

    'sheets' => [
        'balance_sheet_id' => env('GOOGLE_SHEETS_BALANCE_SHEET_ID'),
        'balance_sheet_tab' => env('GOOGLE_SHEETS_BALANCE_TAB', 'balances'),
        'stats_tab' => env('GOOGLE_SHEETS_STATS_TAB', 'Stats'),
        'cache_ttl' => env('GOOGLE_SHEETS_CACHE_TTL', 300),
    ],

    'forge' => [
        'heartbeat_url' => env('FORGE_HEARTBEAT_URL'),
    ],

];
