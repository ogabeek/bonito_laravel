<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Repositories\LessonRepository;
use App\Services\LessonStatisticsService;
use Carbon\Carbon;

class StudentController extends Controller
{
    // Show student dashboard
    public function dashboard(Student $student, LessonRepository $lessonRepo, LessonStatisticsService $statsService)
    {
        // Get upcoming and past lessons for this student
        $upcomingLessons = $lessonRepo->getUpcomingForStudent($student->id);
        $pastLessons = $lessonRepo->getPastForStudent($student->id)
            ->groupBy(fn($lesson) => $lesson->class_date->format('Y-m'));

        // Overall stats for this student (all lessons)
        $allLessons = $lessonRepo->getForStudent($student->id);
        $stats = $statsService->calculateStats($allLessons);

        return view('student.dashboard', compact('student', 'upcomingLessons', 'pastLessons', 'stats'));
    }
}
