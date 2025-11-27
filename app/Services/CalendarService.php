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
        $year = $request?->get('year') ?? now()->year;
        $month = $request?->get('month') ?? now()->month;

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
