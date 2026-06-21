<?php

use App\Models\Student;
use App\Services\GoogleSheetsClient;
use App\Services\PaymentsService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->travelTo(Carbon\Carbon::parse('2026-06-22'));
    config([
        'billing.journal_start' => '2025-12-01',
        'services.sheets.payments_tab' => 'Payments',
    ]);
    Cache::clear();
});

it('parses journal payments using common European sheet formats', function () {
    $sheets = $this->mock(GoogleSheetsClient::class);
    $sheets->shouldReceive('initialize')->once()->with(true)->andReturnTrue();
    $sheets->shouldReceive('read')->once()->with('Payments')->andReturn(collect([
        [],
        ['Date', 'Student', 'Number of hours'],
        ['21/06/26', '  Jane   Doe ', '2,5'],
        ['2025-11-30', 'Jane Doe', '10'],
        ['23/06/2026', 'Jane Doe', '4'],
        ['not-a-date', 'Jane Doe', '3'],
    ]));

    $events = app(PaymentsService::class)->forStudent(new Student(['name' => 'jane doe']));

    expect($events)->toHaveCount(1)
        ->and($events->first())->toMatchArray([
            'name' => 'Jane   Doe',
            'date' => '2026-06-21',
            'hours' => 2.5,
        ]);
});

it('does not cache an empty payments response', function () {
    $sheets = $this->mock(GoogleSheetsClient::class);
    $sheets->shouldReceive('initialize')->twice()->with(true)->andReturnTrue();
    $sheets->shouldReceive('read')->twice()->with('Payments')->andReturn(
        collect(),
        collect([
            ['Date', 'Student', 'Number of hours'],
            ['2026-06-21', 'Jane Doe', '1'],
        ]),
    );

    $service = app(PaymentsService::class);

    expect($service->journalPaymentsByName())->toBeEmpty()
        ->and($service->journalPaymentsByName())->toHaveKey('jane doe');
});
