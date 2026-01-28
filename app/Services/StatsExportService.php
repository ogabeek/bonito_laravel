<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class StatsExportService
{
    /**
     * Export stats to a Google Sheet tab (overwrites the tab content).
     */
    public function export(array $payload): bool
    {
        $sheetId = config('services.sheets.balance_sheet_id');
        $tab = config('services.sheets.stats_tab', 'Stats');
        $credentialsPath = config('services.google.credentials_path');

        if (!$sheetId || !$credentialsPath || !file_exists($credentialsPath)) {
            Log::warning('StatsExportService: missing sheet configuration or credentials');
            return false;
        }

        try {
            $client = new Client();
            $client->setAuthConfig($credentialsPath);
            $client->setScopes([Sheets::SPREADSHEETS]);
            $service = new Sheets($client);

            $rows = $this->buildRows($payload);

            // Clear existing tab then write fresh data
            $service->spreadsheets_values->clear($sheetId, $tab, new ClearValuesRequest());
            $body = new ValueRange(['values' => $rows]);
            $service->spreadsheets_values->update($sheetId, $tab, $body, ['valueInputOption' => 'RAW']);

            return true;
        } catch (\Throwable $e) {
            Log::error('StatsExportService: export failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function buildRows(array $payload): array
    {
        $currentMonthLabel = $payload['currentMonth']->format('F Y');
        $modeLabel = $payload['billing'] ? 'Billing (26-25)' : 'Calendar';
        $periodStats = $payload['periodStats'];
        $studentStats = $payload['studentStats'];
        $teacherStats = $payload['teacherStats'];
        $students = $payload['students'];
        $teachers = $payload['teachers'];
        $yearStatsByMonth = $payload['yearStatsByMonth'];

        $rows = [];

        $rows[] = ['Period', $currentMonthLabel, $modeLabel];
        $rows[] = ['Generated at', now()->toDateTimeString()];
        $rows[] = [];

        // Summary
        $rows[] = ['Summary', 'Done', 'C', 'CT', 'A', 'Total'];
        $rows[] = [
            '',
            $periodStats['completed'] ?? 0,
            $periodStats['student_cancelled'] ?? 0,
            $periodStats['teacher_cancelled'] ?? 0,
            $periodStats['student_absent'] ?? 0,
            $periodStats['total'] ?? 0,
        ];
        $rows[] = [];

        // Students
        $rows[] = ['Students', 'Done', 'C', 'CT', 'A', 'Total', 'Balance'];
        foreach ($students as $student) {
            $stats = $studentStats[$student->id] ?? ['completed' => 0, 'student_cancelled' => 0, 'teacher_cancelled' => 0, 'student_absent' => 0, 'total' => 0];
            $rows[] = [
                $student->name,
                $stats['completed'] ?? 0,
                $stats['student_cancelled'] ?? 0,
                $stats['teacher_cancelled'] ?? 0,
                $stats['student_absent'] ?? 0,
                $stats['total'] ?? 0,
                $student->class_balance ?? '',
            ];
        }
        $rows[] = [];

        // Teachers
        $rows[] = ['Teachers', 'Done', 'C', 'CT', 'A', 'Total'];
        foreach ($teachers as $teacher) {
            $stats = $teacherStats[$teacher->id] ?? ['completed' => 0, 'student_cancelled' => 0, 'teacher_cancelled' => 0, 'student_absent' => 0, 'total' => 0];
            $rows[] = [
                $teacher->name,
                $stats['completed'] ?? 0,
                $stats['student_cancelled'] ?? 0,
                $stats['teacher_cancelled'] ?? 0,
                $stats['student_absent'] ?? 0,
                $stats['total'] ?? 0,
            ];
        }
        $rows[] = [];

        // Year-to-date by month
        $rows[] = ['Year to Date', 'Done', 'C', 'CT', 'A', 'Total'];
        foreach ($yearStatsByMonth as $ym => $stats) {
            [$year, $month] = explode('-', $ym);
            $label = \Carbon\Carbon::createFromDate($year, $month, 1)->format('M');
            $rows[] = [
                $label,
                $stats['completed'] ?? 0,
                $stats['student_cancelled'] ?? 0,
                $stats['teacher_cancelled'] ?? 0,
                $stats['student_absent'] ?? 0,
                $stats['total'] ?? 0,
            ];
        }

        return $rows;
    }
}
