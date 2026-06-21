<?php

return [
    'period_start_day' => env('BILLING_PERIOD_START_DAY', 24),
    'period_end_day' => env('BILLING_PERIOD_END_DAY', 23),

    // Date the lesson journal started. Payments and lessons before this are
    // collapsed into each student's opening balance (see BalanceLedgerService).
    'journal_start' => env('BILLING_JOURNAL_START', '2025-12-01'),
];
