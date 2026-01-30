<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

function getSheetData($sheetId, $range) {
    $credPath = env('GOOGLE_APPLICATION_CREDENTIALS');
    
    $client = new \Google\Client();
    $client->setAuthConfig($credPath);
    $client->addScope(\Google\Service\Sheets::SPREADSHEETS_READONLY);
    $service = new \Google\Service\Sheets($client);
    $response = $service->spreadsheets_values->get($sheetId, $range);
    
    return $response->getValues();
}

// Uncomment to test:
// $data = getSheetData(env('GOOGLE_SHEETS_BALANCE_SHEET_ID'), env('GOOGLE_SHEETS_BALANCE_TAB'));
// var_dump($data);
