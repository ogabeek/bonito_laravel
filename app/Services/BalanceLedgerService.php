<?php

namespace App\Services;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use App\Models\Student;
use App\Repositories\LessonRepository;
use Illuminate\Support\Collection;

/**
 * * SERVICE: Per-class running-balance ledger for a student.
 *
 * Merges dated payment credits (+classes) with chargeable lessons (-1 each)
 * from the journal start, beginning at an opening balance derived so the ledger
 * always ends at the student's real balance (Paid classes - used):
 *
 *   opening = paid - payments_since_cutoff
 *           = current_balance + chargeable_since_cutoff - payments_since_cutoff
 *
 * Everything before the cutoff is absorbed into the opening, so the old
 * messy/undated payment history is never read. A weird opening (e.g. negative)
 * is a useful signal that a student's payment rows need cleaning.
 */
class BalanceLedgerService
{
    /** @var array<int, LessonStatus> Statuses the student still pays for. */
    protected array $chargeable = [LessonStatus::COMPLETED, LessonStatus::STUDENT_ABSENT];

    public function __construct(
        protected BalanceService $balanceService,
        protected PaymentsService $paymentsService,
        protected LessonRepository $lessonRepository,
    ) {}

    /**
     * @return array{
     *   student: Student, cutoff: string, paid: ?float, used: int,
     *   payments_total: float, opening: ?float,
     *   current_balance: ?float, computed_end: ?float, has_balance_data: bool,
     *   entries: Collection<int, mixed>
     * }
     */
    public function forStudent(Student $student): array
    {
        $cutoff = config('billing.journal_start', '2025-12-01');
        $today = now()->toDateString();

        $paid = $this->paidClasses($student);
        $payments = $this->paymentsService->forStudent($student);

        // Lessons within [cutoff, today], oldest first.
        /** @var Collection<int, Lesson> $lessons */
        $lessons = $this->lessonRepository->getForStudent($student->id, ['teacher'])
            ->filter(function (Lesson $lesson) use ($cutoff, $today) {
                $date = $lesson->class_date->toDateString();

                return $date >= $cutoff && $date <= $today;
            })
            ->sortBy('class_date');

        $usedSinceCutoff = $lessons->filter(fn ($l) => in_array($l->status, $this->chargeable, true))->count();
        $paymentsTotal = (float) $payments->sum('hours');

        $currentBalance = $paid !== null ? $paid - $usedSinceCutoff : null;
        $opening = $paid !== null ? $paid - $paymentsTotal : null;

        $entries = $this->applyRunningBalance($this->mergeEntries($lessons, $payments), $opening);

        $last = $entries->last();
        $computedEnd = is_array($last) ? ($last['balance'] ?? null) : $opening;

        return [
            'student' => $student,
            'cutoff' => $cutoff,
            'paid' => $paid,
            'used' => $usedSinceCutoff,
            'payments_total' => $paymentsTotal,
            'opening' => $opening,
            'current_balance' => $currentBalance,
            'computed_end' => is_numeric($computedEnd) ? (float) $computedEnd : null,
            'has_balance_data' => $paid !== null,
            'entries' => $entries,
        ];
    }

    /** Paid classes (Q from the balances sheet) for the student, numeric only. */
    protected function paidClasses(Student $student): ?float
    {
        $raw = $this->balanceService->getBalanceForUuid($student->uuid);

        return is_numeric($raw) ? (float) $raw : null;
    }

    /**
     * Merge lessons (-1 if chargeable, else 0) and payments (+classes) into one
     * date-ordered list. Payments sort before lessons on the same day so a
     * same-day top-up shows before the class it funded.
     *
     * @param  Collection<int, Lesson>  $lessons
     * @param  Collection<int, mixed>  $payments
     * @return Collection<int, mixed>
     */
    protected function mergeEntries(Collection $lessons, Collection $payments): Collection
    {
        $entries = collect();

        foreach ($payments as $payment) {
            /** @var array<string, mixed> $payment */
            $entries->push([
                'date' => $payment['date'],
                'order' => 0,
                'type' => 'payment',
                'label' => 'Payment',
                'detail' => null,
                'delta' => (float) $payment['hours'],
                'status' => null,
            ]);
        }

        foreach ($lessons as $lesson) {
            $charge = in_array($lesson->status, $this->chargeable, true);
            $entries->push([
                'date' => $lesson->class_date->toDateString(),
                'order' => 1,
                'type' => 'lesson',
                'label' => $lesson->status->label(),
                'detail' => $lesson->teacher?->name,
                'delta' => $charge ? -1.0 : 0.0,
                'status' => $lesson->status,
            ]);
        }

        return $entries->sortBy([['date', 'asc'], ['order', 'asc']])->values();
    }

    /**
     * @param  Collection<int, mixed>  $entries
     * @return Collection<int, mixed>
     */
    protected function applyRunningBalance(Collection $entries, ?float $opening): Collection
    {
        if ($opening === null) {
            return $entries->map(function ($entry) {
                /** @var array<string, mixed> $entry */
                $entry['balance'] = null;

                return $entry;
            });
        }

        $running = $opening;

        return $entries->map(function ($entry) use (&$running) {
            /** @var array<string, mixed> $entry */
            $running += (float) $entry['delta'];
            $entry['balance'] = $running;

            return $entry;
        });
    }
}
