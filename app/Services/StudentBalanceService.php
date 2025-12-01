<?php

namespace App\Services;

use App\Models\Student;
use App\Repositories\LessonRepository;
use Illuminate\Support\Collection;

class StudentBalanceService
{
    public function __construct(
        protected BalanceService $balanceService,
        protected LessonRepository $lessonRepository
    ) {}

    /**
     * Enrich students with balance and teacher information
     *
     * @return Collection
     */
    public function enrichStudentsWithBalance(): Collection
    {
        $balances = $this->balanceService->getBalances();
        $usedCounts = $this->lessonRepository->getUsedCountsByStudent();

        return Student::withFullDetails()->get()->map(function($student) use ($balances, $usedCounts) {
            $student->teacher_ids = $student->teachers->pluck('id')->toArray();
            $paid = $balances[$student->uuid] ?? null;
            $used = $usedCounts[$student->id] ?? 0;
            $student->class_balance = $paid !== null ? ($paid - $used) : null;
            return $student;
        });
    }
}
