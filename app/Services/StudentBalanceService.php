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
     * * Adds paid_classes, used_classes, class_balance properties to each student
     * ! Balance can be negative (student owes classes)
     */
    public function enrichStudentsWithBalance(): Collection
    {
        $balances = $this->balanceService->getBalances();          // From Google Sheets
        $usedCounts = $this->lessonRepository->getUsedCountsByStudent(); // From database

        return Student::withFullDetails()->get()->map(function ($student) use ($balances, $usedCounts) {
            $student->teacher_ids = $student->teachers->pluck('id')->toArray();

            $paid = $balances[$student->uuid] ?? null;
            $used = $usedCounts[$student->id] ?? 0;

            // * Dynamic properties added at runtime (not saved to DB)
            $student->paid_classes = $paid !== null ? (int) $paid : null;
            $student->used_classes = (int) $used;
            $student->class_balance = $paid !== null ? ((int) $paid - (int) $used) : null;

            return $student;
        });
    }
}
