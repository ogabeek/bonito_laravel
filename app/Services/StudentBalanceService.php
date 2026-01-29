<?php

namespace App\Services;

use App\Models\Student;
use App\Repositories\LessonRepository;
use Illuminate\Support\Collection;

/**
 * ! SERVICE: Student Balance Service
 * * Purpose: Combines Google Sheets payment data with database lesson data
 * * Why: Creates the final balance calculation (Paid - Used = Balance)
 * * What: Enriches Student objects with balance properties
 */
class StudentBalanceService
{
    /**
     * ? Constructor injection: Laravel automatically provides these dependencies
     * * BalanceService = Gets "Paid Classes" from Google Sheets
     * * LessonRepository = Gets "Used Classes" (completed lessons) from database
     */
    public function __construct(
        protected BalanceService $balanceService,
        protected LessonRepository $lessonRepository
    ) {}

    /**
     * * Main method: Add balance calculations to all students
     * * Flow: Fetch students → Get paid classes → Get used classes → Calculate balance
     * @return Collection Students with added properties: paid_classes, used_classes, class_balance
     */
    public function enrichStudentsWithBalance(): Collection
    {
        // * Step 1: Get payment data from Google Sheets [uuid => paid_count]
        $balances = $this->balanceService->getBalances();
        
        // * Step 2: Get completed lesson counts from database [student_id => used_count]
        $usedCounts = $this->lessonRepository->getUsedCountsByStudent();

        // * Step 3: Load all students with their relationships (teachers, lessons)
        return Student::withFullDetails()->get()->map(function($student) use ($balances, $usedCounts) {
            // ? Add teacher IDs as array for easy access
            $student->teacher_ids = $student->teachers->pluck('id')->toArray();
            
            // * Step 4: Get this student's data
            $paid = $balances[$student->uuid] ?? null;  // From Google Sheets
            $used = $usedCounts[$student->id] ?? 0;     // From database
            
            // * Step 5: Add balance properties to student object
            // ! Important: Cast to int because Google Sheets returns strings
            $student->paid_classes = $paid !== null ? (int) $paid : null;
            $student->used_classes = (int) $used;
            // ? Formula: Balance = Paid - Used (can be negative if student owes classes)
            $student->class_balance = $paid !== null ? ((int) $paid - (int) $used) : null;
            
            return $student;
        });
    }
}
