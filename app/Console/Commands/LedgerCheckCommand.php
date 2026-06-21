<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\BalanceLedgerService;
use App\Services\PaymentsService;
use Illuminate\Console\Command;

/**
 * Reconciles the per-class running-balance ledger against the balances sheet
 * for every student. Verification tool for Phase 1 — eyeball before any UI.
 *
 *   php artisan billing:ledger-check
 *   php artisan billing:ledger-check --student="Ivan"
 */
class LedgerCheckCommand extends Command
{
    protected $signature = 'billing:ledger-check {--student= : Filter to students whose name matches}';

    protected $description = 'Reconcile each student\'s running-balance ledger against the balances sheet';

    public function handle(BalanceLedgerService $ledger, PaymentsService $payments): int
    {
        $students = Student::query()
            ->when($this->option('student'), fn ($q, $name) => $q->where('name', 'like', "%{$name}%"))
            ->orderBy('name')
            ->get();

        $rows = [];
        $flaggedCount = 0;

        foreach ($students as $student) {
            $data = $ledger->forStudent($student);

            $reconciles = $data['has_balance_data']
                && abs(($data['computed_end'] ?? 0) - ($data['current_balance'] ?? 0)) < 0.001;

            $flags = [];
            if (! $data['has_balance_data']) {
                $flags[] = 'NO-BAL';
            }
            if ($data['opening'] !== null && $data['opening'] < 0) {
                $flags[] = 'OPEN<0';
            }
            if ($data['has_balance_data'] && ! $reconciles) {
                $flags[] = 'MISMATCH';
            }
            if ($flags) {
                $flaggedCount++;
            }

            $rows[] = [
                $student->name,
                $this->fmt($data['paid']),
                $this->fmt($data['payments_total']),
                (string) $data['used'],
                $this->fmt($data['opening']),
                $this->fmt($data['computed_end']),
                $this->fmt($data['current_balance']),
                $flags ? implode(' ', $flags) : 'ok',
            ];
        }

        $this->table(
            ['Student', 'Paid(Q)', '+Pay', '-Used', 'Opening', 'End', 'Balance', 'Flags'],
            $rows
        );

        $this->newLine();
        $this->info(sprintf('Students: %d   |   Flagged: %d', $students->count(), $flaggedCount));

        $this->reportUnmatchedPayments($payments);

        return self::SUCCESS;
    }

    /** Payment rows whose student name matches no student in the DB. */
    protected function reportUnmatchedPayments(PaymentsService $payments): void
    {
        $studentKeys = Student::pluck('name')
            ->map(fn ($name) => PaymentsService::normalize($name))
            ->all();

        $unmatched = $payments->journalPaymentsByName()
            ->reject(fn ($events, $key) => in_array($key, $studentKeys, true));

        if ($unmatched->isEmpty()) {
            $this->info('All journal-era payment rows matched a student.');

            return;
        }

        $this->newLine();
        $this->warn('Unmatched payment names (journal era) — not linked to any student:');
        foreach ($unmatched as $events) {
            $first = $events->first();
            $this->line(sprintf(
                '  - %s  (%d row(s), %s classes)',
                $first['name'] ?? '?',
                $events->count(),
                $this->fmt((float) $events->sum('hours')),
            ));
        }
    }

    protected function fmt(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
