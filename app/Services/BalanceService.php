<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BalanceService
{
    public function getBalances(): array
    {
        $sheetId = config('services.sheets.balance_sheet_id');
        $tab = config('services.sheets.balance_sheet_tab', 'balances');

        if (!$sheetId) {
            return [];
        }

        return Cache::remember('balances.sheet', 300, function () use ($sheetId, $tab) {
            $credentialsPath = env('GOOGLE_APPLICATION_CREDENTIALS');

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                Log::warning('BalanceService: credentials missing or not found', ['path' => $credentialsPath]);
                return [];
            }

            try {
                $client = new \Google\Client();
                $client->setAuthConfig($credentialsPath);
                $client->setScopes([\Google\Service\Sheets::SPREADSHEETS_READONLY]);
                $service = new \Google\Service\Sheets($client);

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

            $headers = array_map('strtolower', $rows->pull(0));

            $uuidIndex = array_search('uuid', $headers);
            $balanceIndex = array_search('balance', $headers);

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

    public function getBalanceForUuid(?string $uuid): ?int
    {
        if (!$uuid) return null;
        $balances = $this->getBalances();
        return $balances[$uuid] ?? null;
    }
}
