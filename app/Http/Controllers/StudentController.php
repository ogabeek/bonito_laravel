<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Repositories\LessonRepository;
use App\Services\LessonStatisticsService;

/**
 * ! CONTROLLER: StudentController - Student Progress Page
 *
 * * This is the simplest controller - just ONE action
 * * Students can view their lesson history via a public UUID link
 *
 * * Public Access Flow:
 * * ┌────────────────────────────────────────────────────────────────┐
 * * │ Student URL: /student/550e8400-e29b-41d4-a716-446655440000    │
 * * │                                                               │
 * * │ 1. No login required (public page)                            │
 * * │ 2. UUID is unguessable (security through obscurity)           │
 * * │ 3. Parent/student can bookmark and access anytime             │
 * * │ 4. Shows lesson history with topics, homework, stats          │
 * * └────────────────────────────────────────────────────────────────┘
 *
 * ? Why UUID instead of ID?
 * ? - ID: /student/1, /student/2 → Easy to guess other students
 * ? - UUID: /student/abc123... → Impossible to guess, secure sharing
 */
class StudentController extends Controller
{
    /**
     * ! ACTION: Show student's lesson history
     * * Route: GET /student/{student}
     * * View: resources/views/student/dashboard.blade.php
     *
     * * No authentication required - public page
     * * Student is found by UUID (see Student::getRouteKeyName())
     *
     * @param  Student  $student  // * Auto-resolved by UUID from URL
     * @param  LessonRepository  $lessonRepo  // * Database queries
     * @param  LessonStatisticsService  $statsService  // * Calculate stats
     */
    public function dashboard(Student $student, LessonRepository $lessonRepo, LessonStatisticsService $statsService)
    {
        // * Get PAST lessons only (not future scheduled ones)
        // * groupBy() organizes by month: ['2024-01' => [...], '2024-02' => [...]]
        $pastLessons = $lessonRepo->getPastForStudent($student->id)
            ->groupBy(fn ($lesson) => $lesson->class_date->format('Y-m'));

        // * Get ALL lessons for statistics (including today/future if any)
        $allLessons = $lessonRepo->getForStudent($student->id);

        // * Calculate summary: total lessons, completed, cancelled, etc.
        $stats = $statsService->calculateStats($allLessons);

        return view('student.dashboard', compact('student', 'pastLessons', 'stats'));
    }
}
