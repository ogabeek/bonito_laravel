<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Fetches "Paid Classes" data from Google Sheets.
 *
 * Payment tracking happens in Google Sheets (admin manual entry).
 * Returns array of [uuid => paid_classes_count].
 */
class BalanceService
{
    public function __construct(
        protected GoogleSheetsClient $sheetsClient
    ) {}

    /**
     * Get all student balances from Google Sheets.
     *
     * @return array<string, int|string> Map of UUID to paid classes count
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

            // First row = headers, convert to lowercase for matching
            $headers = array_map('strtolower', $rows->pull(0));

            // Find column indexes for UUID (student ID) and Paid Classes
            $uuidIndex = array_search('uuid', $headers);
            $balanceIndex = array_search('paid classes', $headers);

            if ($uuidIndex === false || $balanceIndex === false) {
                return [];
            }

            // Build array mapping UUID → Paid Classes count
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
     * Get paid classes count for a specific student.
     *
     * @param  string|null  $uuid  Student's UUID
     * @return int|null Number of paid classes, or null if not found
     */
    public function getBalanceForUuid(?string $uuid): ?int
    {
        if (! $uuid) {
            return null;
        }

        $balances = $this->getBalances();

        return $balances[$uuid] ?? null;
    }
}
