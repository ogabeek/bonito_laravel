<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Handles all Google Sheets API interactions.
 *
 * Centralizes authentication, reading, and writing operations
 * to Google Sheets for the application.
 */
class GoogleSheetsClient
{
    protected ?Sheets $service = null;

    protected ?string $sheetId = null;

    /**
     * Initialize the Google Sheets client.
     *
     * @param  bool  $readonly  Whether to use readonly scope
     * @return bool True if initialization was successful
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
     * Read values from a specific tab.
     *
     * @param  string  $tab  The tab/sheet name to read from
     * @return Collection<int, array<int, mixed>> Collection of rows
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
     * Write values to a specific tab (clears and replaces content).
     *
     * @param  string  $tab  The tab/sheet name to write to
     * @param  array<int, array<int, mixed>>  $rows  The rows to write
     * @return bool True if write was successful
     */
    public function write(string $tab, array $rows): bool
    {
        if (! $this->service) {
            return false;
        }

        try {
            // Clear existing content
            $this->service->spreadsheets_values->clear(
                $this->sheetId,
                $tab,
                new ClearValuesRequest
            );

            // Write new data
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

    /**
     * Check if the client has been initialized successfully.
     */
    public function isInitialized(): bool
    {
        return $this->service !== null;
    }
}
