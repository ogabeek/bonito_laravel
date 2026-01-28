<?php

use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->service = new CalendarService();
    Carbon::setTestNow(Carbon::create(2025, 6, 15));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('returns current month when no request params', function () {
    $data = $this->service->getMonthData(null);

    expect($data['currentMonth']->year)->toBe(2025)
        ->and($data['currentMonth']->month)->toBe(6);
});

it('returns specified year and month from request', function () {
    $request = Request::create('/', 'GET', ['year' => '2024', 'month' => '3']);
    $data = $this->service->getMonthData($request);

    expect($data['currentMonth']->year)->toBe(2024)
        ->and($data['currentMonth']->month)->toBe(3);
});

it('clamps month to valid range 1-12', function () {
    $request = Request::create('/', 'GET', ['year' => '2025', 'month' => '15']);
    $data = $this->service->getMonthData($request);

    expect($data['currentMonth']->month)->toBe(12);

    $request = Request::create('/', 'GET', ['year' => '2025', 'month' => '0']);
    $data = $this->service->getMonthData($request);

    expect($data['currentMonth']->month)->toBe(1);
});

it('clamps year to valid range 2000-2100', function () {
    $request = Request::create('/', 'GET', ['year' => '1990', 'month' => '6']);
    $data = $this->service->getMonthData($request);

    expect($data['currentMonth']->year)->toBe(2000);

    $request = Request::create('/', 'GET', ['year' => '2200', 'month' => '6']);
    $data = $this->service->getMonthData($request);

    expect($data['currentMonth']->year)->toBe(2100);
});

it('handles non-numeric input gracefully', function () {
    $request = Request::create('/', 'GET', ['year' => 'invalid', 'month' => 'abc']);
    $data = $this->service->getMonthData($request);

    // Falls back to current date
    expect($data['currentMonth']->year)->toBe(2025)
        ->and($data['currentMonth']->month)->toBe(6);
});

it('calculates prev and next months correctly', function () {
    $request = Request::create('/', 'GET', ['year' => '2025', 'month' => '1']);
    $data = $this->service->getMonthData($request);

    expect($data['prevMonth']->year)->toBe(2024)
        ->and($data['prevMonth']->month)->toBe(12)
        ->and($data['nextMonth']->year)->toBe(2025)
        ->and($data['nextMonth']->month)->toBe(2);
});

it('returns correct days in month', function () {
    $request = Request::create('/', 'GET', ['year' => '2024', 'month' => '2']);
    $data = $this->service->getMonthData($request);

    expect($data['daysInMonth'])->toBe(29); // Leap year

    $request = Request::create('/', 'GET', ['year' => '2025', 'month' => '2']);
    $data = $this->service->getMonthData($request);

    expect($data['daysInMonth'])->toBe(28); // Non-leap year
});
