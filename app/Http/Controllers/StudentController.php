<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Repositories\LessonRepository;
use App\Services\LessonStatisticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * StudentController - Public student progress page
 *
 * No auth required - uses UUID for secure public sharing.
 */
class StudentController extends Controller
{
    public function dashboard(Request $request, Student $student, LessonRepository $lessonRepo, LessonStatisticsService $statsService): View
    {
        $allLessons = $lessonRepo->getForStudent($student->id);

        $availableYears = $allLessons->toBase()
            ->map(fn ($l) => $l->class_date->year)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();
        $selectedYear = (int) $request->input('year', now()->year);

        $yearLessons = $allLessons->filter(fn ($l) => $l->class_date->year === $selectedYear);
        $lessonsByMonth = $yearLessons->groupBy(fn ($l) => $l->class_date->format('Y-m'));
        $stats = $statsService->calculateStats($yearLessons);

        $distributionStart = Carbon::create($selectedYear, 1, 1);
        $weeklyDistribution = $statsService->calculateWeeklyDistributionForRange(
            $allLessons,
            $distributionStart,
            $distributionStart->copy()->endOfYear()
        );

        return view('student.dashboard', compact(
            'student', 'lessonsByMonth', 'stats', 'weeklyDistribution',
            'availableYears', 'selectedYear'
        ));
    }
}
