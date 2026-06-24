<?php

namespace App\Services;

use App\Concerns\CachesNonEmpty;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * * SERVICE: Reads individual payment events from the Google Sheets "Payments" tab.
 * ! Supplies the WHEN of payments (dated +classes credits) that the single
 *   "Paid classes" balance column cannot express. Balance totals still come
 *   from BalanceService; this is purely the per-payment timeline.
 *
 * Only journal-era payments (date >= billing.journal_start) are returned —
 * older/undated rows are collapsed into the opening balance by BalanceLedgerService.
 * Matched to students by normalized name (UUID column on the tab is the future fix).
 */
class PaymentsService
{
    use CachesNonEmpty;

    protected const CACHE_KEY = 'payments.journal';

    public function __construct(
        protected GoogleSheetsClient $sheetsClient
    ) {}

    /**
     * * Journal-era payment events grouped by normalized student name.
     * * Each group value is a Collection of {name, date, hours} rows.
     *
     * @return Collection<string, mixed>
     */
    public function journalPaymentsByName(): Collection
    {
        /** @var array<int, array{key: string, name: string, date: string, hours: float}> $events */
        $events = $this->rememberNonEmpty(
            self::CACHE_KEY,
            config('services.sheets.cache_ttl', 300),
            fn (): array => $this->fetch(),
        );

        $grouped = [];
        foreach ($events as $event) {
            $grouped[$event['key']] ??= collect();
            $grouped[$event['key']]->push([
                'name' => $event['name'],
                'date' => $event['date'],
                'hours' => $event['hours'],
            ]);
        }

        return collect($grouped);
    }

    /**
     * * Journal-era payments for one student (matched by normalized name).
     *
     * Each row is an array: {name, date, hours}.
     *
     * @return Collection<int, mixed>
     */
    public function forStudent(Student $student): Collection
    {
        $events = $this->journalPaymentsByName()->get(self::normalize($student->name));

        return $events instanceof Collection ? $events : collect();
    }

    /** * Force a fresh read on next call (wired into the billing refresh action). */
    public function refreshCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * * Read + parse the Payments tab into a flat array of journal-era events.
     *
     * @return array<int, array{key: string, name: string, date: string, hours: float}>
     */
    protected function fetch(): array
    {
        if (! $this->sheetsClient->initialize(readonly: true)) {
            return [];
        }

        return $this->parse($this->sheetsClient->read(
            config('services.sheets.payments_tab', 'Payments')
        ));
    }

    /**
     * * Locate the header row dynamically (the tab has a blank first row),
     * * map columns by header name, then keep numeric, dated, journal-era rows.
     *
     * @param  Collection<int, array<int, string>>  $rows
     * @return array<int, array{key: string, name: string, date: string, hours: float}>
     */
    protected function parse(Collection $rows): array
    {
        $headerIdx = $rows->search(fn ($row) => $this->looksLikeHeader($row));
        if ($headerIdx === false) {
            return [];
        }

        $headers = array_map(fn ($h) => trim(mb_strtolower((string) $h)), $rows[$headerIdx]);
        $iDate = array_search('date', $headers, true);
        $iName = array_search('student', $headers, true);
        $iHours = array_search('number of hours', $headers, true);

        if ($iDate === false || $iName === false || $iHours === false) {
            return [];
        }

        $cutoff = config('billing.journal_start', '2025-12-01');
        $today = now()->toDateString();

        $events = [];
        foreach ($rows->slice($headerIdx + 1) as $row) {
            $name = trim((string) ($row[$iName] ?? ''));
            $date = $this->parseDate($row[$iDate] ?? null);
            $hours = $this->parseNumber($row[$iHours] ?? null);

            // Skip blanks, undated/pre-journal rows (baked into opening), and
            // future-dated rows (not yet reflected in the balance).
            if ($name === '' || $date === null || $hours === null) {
                continue;
            }
            if ($date < $cutoff || $date > $today) {
                continue;
            }

            $events[] = [
                'key' => self::normalize($name),
                'name' => $name,
                'date' => $date,
                'hours' => $hours,
            ];
        }

        return $events;
    }

    /** @param  array<int, string>  $row */
    protected function looksLikeHeader(array $row): bool
    {
        $lower = array_map(fn ($c) => trim(mb_strtolower((string) $c)), $row);

        return in_array('date', $lower, true) && in_array('student', $lower, true);
    }

    protected function parseDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'j/n/y', 'j/n/Y', 'j.n.y', 'j.n.Y', 'j-n-y', 'j-n-Y'] as $format) {
            try {
                return Carbon::createFromFormat('!'.$format, $value)->toDateString();
            } catch (\Throwable) {
                // Try the next known Sheets date format.
            }
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    protected function parseNumber(mixed $value): ?float
    {
        $clean = str_replace([' ', "\u{00A0}"], '', trim((string) $value));
        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $clean = $lastComma > $lastDot
                ? str_replace(',', '.', str_replace('.', '', $clean))
                : str_replace(',', '', $clean);
        } elseif ($lastComma !== false) {
            $clean = str_replace(',', '.', $clean);
        }

        return is_numeric($clean) ? (float) $clean : null;
    }

    /** * Normalized match key: lowercased, trimmed, collapsed whitespace. */
    public static function normalize(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($name)));
    }
}
