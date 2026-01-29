<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ! SERVICE: Balance Service
 * * Purpose: Fetches "Paid Classes" data from Google Sheets
 * * Why: Payment tracking happens in Google Sheets (admin manual entry)
 * * What: Returns array of [uuid => paid_classes_count]
 * TODO: Consider moving to database if payment system becomes automated
 */
class BalanceService
{
    /**
     * * Main method: Get all student balances from Google Sheets
     * @return array [uuid => paid_classes_count]
     */
    public function getBalances(): array
    {
        // ? Get config values from .env via config/services.php
        $sheetId = config('services.sheets.balance_sheet_id');
        $tab = config('services.sheets.balance_sheet_tab', 'balances');

        if (!$sheetId) {
            return [];
        }

        // * Cache for 5 minutes (300 seconds) to avoid hitting Google API repeatedly
        $cacheTtl = config('services.sheets.cache_ttl', 300);

        return Cache::remember('balances.sheet', $cacheTtl, function () use ($sheetId, $tab) {
            $credentialsPath = config('services.google.credentials_path');

            // ! Guard: Stop if credentials missing or invalid
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                Log::warning('BalanceService: credentials missing or not found', ['path' => $credentialsPath]);
                return [];
            }

            try {
                // * Step 1: Authenticate with Google Sheets API
                $client = new \Google\Client();
                $client->setAuthConfig($credentialsPath);
                $client->setScopes([\Google\Service\Sheets::SPREADSHEETS_READONLY]);
                $service = new \Google\Service\Sheets($client);

                // * Step 2: Fetch entire tab as 2D array (rows x columns)
                $response = $service->spreadsheets_values->get($sheetId, $tab);
                $values = $response->getValues() ?? [];
                $rows = collect($values);
            } catch (\Throwable $e) {
                Log::error('BalanceService: error fetching balances', ['error' => $e->getMessage()]);
                return [];
            }

            if ($rows->count() === 0) {
                return [];
            }

            // * Step 3: First row = headers, convert to lowercase for matching
            $headers = array_map('strtolower', $rows->pull(0));

            // * Step 4: Find column indexes for UUID (student ID) and Paid Classes
            $uuidIndex = array_search('uuid', $headers);
            $balanceIndex = array_search('paid classes', $headers);

            // ! Guard: Stop if required columns not found
            if ($uuidIndex === false || $balanceIndex === false) {
                return [];
            }

            // * Step 5: Build array mapping UUID → Paid Classes count
            $balances = [];
            foreach ($rows as $row) {
                $uuid = $row[$uuidIndex] ?? null;
                $balance = $row[$balanceIndex] ?? null;
                if ($uuid !== null && $balance !== null) {
                    // ? Cast to int because Google Sheets returns everything as strings
                    $balances[$uuid] = is_numeric($balance) ? (int) $balance : $balance;
                }
            }

            return $balances;
        });
    }

    /**
     * * Get paid classes count for a specific student
     * @param string|null $uuid Student's UUID
     * @return int|null Number of paid classes, or null if not found
     */
    public function getBalanceForUuid(?string $uuid): ?int
    {
        if (!$uuid) return null;
        $balances = $this->getBalances();
        return $balances[$uuid] ?? null;
    }
}

