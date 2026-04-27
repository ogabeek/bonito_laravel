<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Repositories\LessonRepository;
use App\Services\LessonStatisticsService;
use Illuminate\View\View;

/**
 * StudentController - Public student progress page
 *
 * No auth required - uses UUID for secure public sharing.
 */
class StudentController extends Controller
{
    public function dashboard(Student $student, LessonRepository $lessonRepo, LessonStatisticsService $statsService): View
    {
        $allLessons = $lessonRepo->getForStudent($student->id);
        $lessonsByMonth = $allLessons->groupBy(fn ($lesson) => $lesson->class_date->format('Y-m'));
        $stats = $statsService->calculateStats($allLessons);
        $distributionStart = now()->copy()->subMonthsNoOverflow(5)->startOfMonth();
        $weeklyDistribution = $statsService->calculateWeeklyDistributionForRange(
            $allLessons,
            $distributionStart,
            $distributionStart->copy()->addYear()->subDay()
        );

        return view('student.dashboard', compact('student', 'lessonsByMonth', 'stats', 'weeklyDistribution'));
    }
}
