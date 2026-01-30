<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Repositories\LessonRepository;
use App\Services\LessonStatisticsService;

/**
 * StudentController - Public student progress page
 *
 * No auth required - uses UUID for secure public sharing.
 */
class StudentController extends Controller
{
    public function dashboard(Student $student, LessonRepository $lessonRepo, LessonStatisticsService $statsService)
    {
        // Past lessons grouped by month
        $pastLessons = $lessonRepo->getPastForStudent($student->id)
            ->groupBy(fn ($lesson) => $lesson->class_date->format('Y-m'));

        $allLessons = $lessonRepo->getForStudent($student->id);
        $stats = $statsService->calculateStats($allLessons);

        return view('student.dashboard', compact('student', 'pastLessons', 'stats'));
    }
}
