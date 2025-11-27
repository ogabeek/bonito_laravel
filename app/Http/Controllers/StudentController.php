<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Carbon\Carbon;

class StudentController extends Controller
{
    // Show student dashboard
    public function dashboard(Student $student)
    {
        // Get all lessons for this student, ordered by date
        $allLessons = $student->lessons()
            ->with('teacher')
            ->orderBy('class_date', 'desc')
            ->get();
        
        $today = now()->startOfDay();
        
        // Split into upcoming and past
        $upcomingLessons = $allLessons->filter(function($lesson) use ($today) {
            return $lesson->class_date >= $today;
        })->sortBy('class_date');
        
        $pastLessons = $allLessons->filter(function($lesson) use ($today) {
            return $lesson->class_date < $today;
        });
        
        return view('student.dashboard', compact('student', 'upcomingLessons', 'pastLessons'));
    }
}
