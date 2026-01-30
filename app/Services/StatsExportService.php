<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * * SERVICE: Exports billing stats to Google Sheets
 * * Used for sharing reports with admin via spreadsheet
 */
class StatsExportService
{
    public function __construct(
        protected GoogleSheetsClient $sheetsClient
    ) {}

    /**
     * * Export stats to Google Sheet (replaces tab content)
     */
    public function export(array $payload): bool
    {
        $tab = config('services.sheets.stats_tab', 'Stats');

        if (! $this->sheetsClient->initialize(readonly: false)) {
            return false;
        }

        $rows = $this->buildRows($payload);

        return $this->sheetsClient->write($tab, $rows);
    }

    /**
     * * Transforms billing payload into 2D array for spreadsheet
     */
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

        // * Header section
        $rows[] = ['Period', $currentMonthLabel, $modeLabel];
        $rows[] = ['Generated at', now()->toDateTimeString()];
        $rows[] = [];

        // * Summary section
        $rows[] = ['Summary', 'Done', 'C', 'CT', 'A', 'Total'];
        $rows[] = $this->buildStatsRow('', $periodStats);
        $rows[] = [];

        // * Students section with balance
        $rows[] = ['Students', 'Done', 'C', 'CT', 'A', 'Total', 'Balance'];
        foreach ($students as $student) {
            $stats = $studentStats[$student->id] ?? $this->emptyStats();
            $row = $this->buildStatsRow($student->name, $stats);
            $row[] = $student->class_balance ?? '';
            $rows[] = $row;
        }
        $rows[] = [];

        // * Teachers section
        $rows[] = ['Teachers', 'Done', 'C', 'CT', 'A', 'Total'];
        foreach ($teachers as $teacher) {
            $stats = $teacherStats[$teacher->id] ?? $this->emptyStats();
            $rows[] = $this->buildStatsRow($teacher->name, $stats);
        }
        $rows[] = [];

        // * Year-to-date by month
        $rows[] = ['Year to Date', 'Done', 'C', 'CT', 'A', 'Total'];
        foreach ($yearStatsByMonth as $ym => $stats) {
            [$year, $month] = explode('-', $ym);
            $label = Carbon::createFromDate($year, $month, 1)->format('M');
            $rows[] = $this->buildStatsRow($label, $stats);
        }

        return $rows;
    }

    /**
     * * Column order: Label, Done, Cancelled, Teacher Cancelled, Absent, Total
     */
    protected function buildStatsRow(string $label, array $stats): array
    {
        return [
            $label,
            $stats['completed'] ?? 0,
            $stats['student_cancelled'] ?? 0,
            $stats['teacher_cancelled'] ?? 0,
            $stats['student_absent'] ?? 0,
            $stats['total'] ?? 0,
        ];
    }

    protected function emptyStats(): array
    {
        return [
            'completed' => 0,
            'student_cancelled' => 0,
            'teacher_cancelled' => 0,
            'student_absent' => 0,
            'total' => 0,
        ];
    }
}
