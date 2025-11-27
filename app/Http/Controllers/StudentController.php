<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Repositories\LessonRepository;
use Carbon\Carbon;

class StudentController extends Controller
{
    // Show student dashboard
    public function dashboard(Student $student, LessonRepository $lessonRepo)
    {
        // Get upcoming and past lessons for this student
        $upcomingLessons = $lessonRepo->getUpcomingForStudent($student->id);
        $pastLessons = $lessonRepo->getPastForStudent($student->id);

        return view('student.dashboard', compact('student', 'upcomingLessons', 'pastLessons'));
    }
}
