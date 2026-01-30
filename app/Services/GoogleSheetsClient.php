<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * * SERVICE: Google Sheets API wrapper
 * * Handles auth, read, and write operations to Google Sheets
 * ! Requires: config/services.php sheets config + credentials JSON file
 */
class GoogleSheetsClient
{
    protected ?Sheets $service = null;

    protected ?string $sheetId = null;

    /**
     * * Must call before read/write. Sets up Google API client.
     * ? readonly: true = Sheets::SPREADSHEETS_READONLY scope (safer)
     */
    public function initialize(bool $readonly = true): bool
    {
        $this->sheetId = config('services.sheets.balance_sheet_id');
        $credentialsPath = config('services.google.credentials_path');

        if (! $this->sheetId || ! $credentialsPath || ! file_exists($credentialsPath)) {
            Log::warning('GoogleSheetsClient: missing configuration or credentials', [
                'sheet_id' => $this->sheetId ? 'set' : 'missing',
                'credentials' => $credentialsPath && file_exists($credentialsPath) ? 'found' : 'missing',
            ]);

            return false;
        }

        try {
            $client = new Client;
            $client->setAuthConfig($credentialsPath);
            $client->setScopes([
                $readonly ? Sheets::SPREADSHEETS_READONLY : Sheets::SPREADSHEETS,
            ]);
            // * Timeout prevents hanging on slow connections
            $client->setHttpClient(new \GuzzleHttp\Client([
                'timeout' => 10,
                'connect_timeout' => 5,
            ]));
            $this->service = new Sheets($client);

            return true;
        } catch (\Throwable $e) {
            Log::error('GoogleSheetsClient: failed to initialize', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * * Read all values from a tab/sheet
     *
     * @param  string  $tab  Sheet tab name (e.g., 'balances', 'Stats')
     */
    public function read(string $tab): Collection
    {
        if (! $this->service) {
            return collect();
        }

        try {
            $response = $this->service->spreadsheets_values->get($this->sheetId, $tab);

            return collect($response->getValues() ?? []);
        } catch (\Throwable $e) {
            Log::error('GoogleSheetsClient: read failed', [
                'tab' => $tab,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * * Write rows to a tab (clears existing content first)
     * ! Destructive - completely replaces tab content
     */
    public function write(string $tab, array $rows): bool
    {
        if (! $this->service) {
            return false;
        }

        try {
            $this->service->spreadsheets_values->clear(
                $this->sheetId,
                $tab,
                new ClearValuesRequest
            );

            $body = new ValueRange(['values' => $rows]);
            $this->service->spreadsheets_values->update(
                $this->sheetId,
                $tab,
                $body,
                ['valueInputOption' => 'RAW']
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('GoogleSheetsClient: write failed', [
                'tab' => $tab,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function isInitialized(): bool
    {
        return $this->service !== null;
    }
}
