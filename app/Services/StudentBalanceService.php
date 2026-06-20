<?php

namespace App\Services;

use App\Models\Student;
use App\Repositories\LessonRepository;
use Illuminate\Support\Collection;

/**
 * * SERVICE: Calculates student balances (Paid - Used = Balance)
 * * Combines Google Sheets payment data + database lesson counts
 */
class StudentBalanceService
{
    public function __construct(
        protected BalanceService $balanceService,
        protected LessonRepository $lessonRepository
    ) {}

    /**
     * * Loads all students and enriches them with balance data from Google Sheets.
     * ? Eager path used for export; the billing page lazy-loads balances and calls
     *   mapBalances() directly to avoid a slow Sheets call on first render.
     */
    public function enrichStudentsWithBalance(): Collection
    {
        $balances = $this->balanceService->getBalances();          // From Google Sheets
        $usedCounts = $this->lessonRepository->getUsedCountsByStudent(); // From database

        return $this->mapBalances(Student::withFullDetails()->get(), $balances, $usedCounts);
    }

    /**
     * * Single source of truth for balance enrichment (Paid - Used = Balance).
     * ! Balance can be negative (student owes classes); null paid = no payment data.
     *
     * @param  Collection<int, Student>  $students
     * @param  array<string, int|string>  $balances  [uuid => paid_classes]
     * @param  Collection<int, int>  $usedCounts  [student_id => used]
     * @return Collection<int, Student>
     */
    public function mapBalances(Collection $students, array $balances, Collection $usedCounts): Collection
    {
        return $students->map(function (Student $student) use ($balances, $usedCounts) {
            $student->teacher_ids = $student->teachers->pluck('id')->toArray();

            $paid = $balances[$student->uuid] ?? null;
            $used = (int) ($usedCounts[$student->id] ?? 0);

            // Runtime-only attributes (see Student model @property docblock).
            $student->paid_classes = $paid !== null ? (int) $paid : null;
            $student->used_classes = $used;
            $student->class_balance = $paid !== null ? ((int) $paid - $used) : null;

            return $student;
        });
    }
}
