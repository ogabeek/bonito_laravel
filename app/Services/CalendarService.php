<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * * SERVICE: Calendar navigation helper
 * * Parses ?year=&month= query params and returns Carbon objects for navigation
 */
class CalendarService
{
    /**
     * * Returns Carbon objects for current, prev, next month based on URL params
     * ! Sanitizes input to prevent invalid dates (year: 2000-2100, month: 1-12)
     */
    public function getMonthData(?Request $request = null): array
    {
        $now = now();

        $year = $request?->query('year');
        $year = is_numeric($year) ? (int) $year : $now->year;
        $year = max(2000, min(2100, $year));

        $month = $request?->query('month');
        $month = is_numeric($month) ? (int) $month : $now->month;
        $month = max(1, min(12, $month));

        $currentMonth = Carbon::createFromDate($year, $month, 1);

        // * copy() prevents mutating the original Carbon instance
        return [
            'currentMonth' => $currentMonth,
            'prevMonth' => $currentMonth->copy()->subMonth(),
            'nextMonth' => $currentMonth->copy()->addMonth(),
            'daysInMonth' => $currentMonth->daysInMonth,
            'monthStart' => $currentMonth->copy()->startOfMonth(),
        ];
    }
}
