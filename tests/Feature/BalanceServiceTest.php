<?php

use App\Services\BalanceService;
use App\Services\GoogleSheetsClient;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::clear();
    config(['services.sheets.balance_sheet_tab' => 'Clients balance']);
});

it('ignores non numeric paid class values from the balance sheet', function () {
    $sheets = $this->mock(GoogleSheetsClient::class);
    $sheets->shouldReceive('initialize')->once()->with(true)->andReturnTrue();
    $sheets->shouldReceive('read')->once()->with('Clients balance')->andReturn(collect([
        ['UUID', 'Paid Classes'],
        ['valid-uuid', '12'],
        ['invalid-uuid', 'check manually'],
        ['blank-uuid', ''],
    ]));

    $service = app(BalanceService::class);

    expect($service->getBalances())->toBe(['valid-uuid' => 12])
        ->and($service->getBalanceForUuid('valid-uuid'))->toBe(12)
        ->and($service->getBalanceForUuid('invalid-uuid'))->toBeNull()
        ->and($service->getBalanceForUuid('blank-uuid'))->toBeNull();
});

it('normalizes invalid cached balance values before returning a single balance', function () {
    Cache::put('balances.sheet', [
        'valid-uuid' => '8',
        'invalid-uuid' => 'not a number',
    ]);

    $service = app(BalanceService::class);

    expect($service->getBalances())->toBe(['valid-uuid' => 8])
        ->and($service->getBalanceForUuid('valid-uuid'))->toBe(8)
        ->and($service->getBalanceForUuid('invalid-uuid'))->toBeNull();
});
