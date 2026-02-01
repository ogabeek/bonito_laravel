<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * * SERVICE: Fetches "Paid Classes" from Google Sheets
 * ! Payment data lives in Google Sheets (manual admin entry), not in database
 * ? Why Google Sheets? Admin prefers spreadsheet for payment tracking
 * 
 * CACHING STRATEGY:
 * - Automatic: Data cached for 5 minutes (300 seconds) to reduce API calls
 * - Manual: Use refreshCache() to force immediate refresh without waiting
 * - Best Practice: Use manual refresh after updating Google Sheets
 */
class BalanceService
{
    public function __construct(
        protected GoogleSheetsClient $sheetsClient
    ) {}

    /**
     * * Returns [uuid => paid_classes_count] from Google Sheets
     * ! Cached for 5 minutes to avoid API rate limits
     */
    public function getBalances(): array
    {
        $tab = config('services.sheets.balance_sheet_tab', 'balances');
        $cacheTtl = config('services.sheets.cache_ttl', 300);

        return Cache::remember('balances.sheet', $cacheTtl, function () use ($tab) {
            if (! $this->sheetsClient->initialize(readonly: true)) {
                return [];
            }

            $rows = $this->sheetsClient->read($tab);

            if ($rows->isEmpty()) {
                return [];
            }

            // * pull(0) removes and returns first row (headers)
            $headers = array_map('strtolower', $rows->pull(0));

            $uuidIndex = array_search('uuid', $headers);
            $balanceIndex = array_search('paid classes', $headers);

            if ($uuidIndex === false || $balanceIndex === false) {
                return [];
            }

            $balances = [];
            foreach ($rows as $row) {
                $uuid = $row[$uuidIndex] ?? null;
                $balance = $row[$balanceIndex] ?? null;

                if ($uuid !== null && $balance !== null) {
                    $balances[$uuid] = is_numeric($balance) ? (int) $balance : $balance;
                }
            }

            return $balances;
        });
    }

    /**
     * Get paid classes for single student by UUID
     */
    public function getBalanceForUuid(?string $uuid): ?int
    {
        if (! $uuid) {
            return null;
        }

        $balances = $this->getBalances();

        return $balances[$uuid] ?? null;
    }

    /**
     * * Clear the balance cache to force fresh fetch from Google Sheets
     * * Use this when you need immediate data without waiting for cache expiry
     */
    public function refreshCache(): void
    {
        Cache::forget('balances.sheet');
    }
}
