<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Repositories\LessonRepository;
use App\Services\LessonStatisticsService;

class StudentController extends Controller
{
    // Show student dashboard
    public function dashboard(Student $student, LessonRepository $lessonRepo, LessonStatisticsService $statsService)
    {
        $pastLessons = $lessonRepo->getPastForStudent($student->id)
            ->groupBy(fn ($lesson) => $lesson->class_date->format('Y-m'));

        $allLessons = $lessonRepo->getForStudent($student->id);
        $stats = $statsService->calculateStats($allLessons);

        return view('student.dashboard', compact('student', 'pastLessons', 'stats'));
    }
}
