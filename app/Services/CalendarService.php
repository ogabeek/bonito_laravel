<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarService
{
    /**
     * Get calendar data for a given request (with year/month query params)
     *
     * @param Request|null $request
     * @return array
     */
    public function getMonthData(?Request $request = null): array
    {
        $now = now();

        // Validate and sanitize year (reasonable range: 2000-2100)
        $year = $request?->query('year');
        $year = is_numeric($year) ? (int) $year : $now->year;
        $year = max(2000, min(2100, $year));

        // Validate and sanitize month (1-12)
        $month = $request?->query('month');
        $month = is_numeric($month) ? (int) $month : $now->month;
        $month = max(1, min(12, $month));

        $currentMonth = Carbon::createFromDate($year, $month, 1);

        return [
            'currentMonth' => $currentMonth,
            'prevMonth' => $currentMonth->copy()->subMonth(),
            'nextMonth' => $currentMonth->copy()->addMonth(),
            'daysInMonth' => $currentMonth->daysInMonth,
            'monthStart' => $currentMonth->copy()->startOfMonth(),
        ];
    }
}
