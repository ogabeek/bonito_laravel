<?php

namespace App\Http\Controllers;

use App\Concerns\LogsActivityActions;
use App\Http\Requests\CreateLessonRequest;
use App\Http\Requests\TeacherLoginRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Repositories\LessonRepository;
use App\Services\AuthenticationService;
use App\Services\CalendarService;
use App\Services\LessonStatisticsService;
use Illuminate\Http\Request;

/**
 * ! CONTROLLER: TeacherController - Teacher Portal
 *
 * * This controller handles teacher-specific functionality:
 * * - Teacher login/logout (each teacher has their own URL)
 * * - Teacher dashboard (view their students and lessons)
 * * - Lesson CRUD (create, update, delete lessons)
 *
 * * Teacher Authentication Flow:
 * * ┌───────────────────────────────────────────────────────────────┐
 * * │ 1. Teacher receives link: /teacher/5                         │
 * * │ 2. Shows login form for Teacher #5                           │
 * * │ 3. Teacher enters password                                   │
 * * │ 4. If correct → session stores teacher_id = 5                │
 * * │ 5. Teacher can only see their own students/lessons           │
 * * └───────────────────────────────────────────────────────────────┘
 *
 * ? Why separate URLs per teacher?
 * ? - Simple: No username needed, just password
 * ? - Secure: Teachers can't guess other teacher IDs
 * ? - Easy: Admin shares the URL directly with teacher
 */
class TeacherController extends Controller
{
    /**
     * * Trait for logging user actions (audit trail)
     */
    use LogsActivityActions;

    /**
     * ! ACTION: Show teacher login form
     * * Route: GET /teacher/{teacher}
     * * View: resources/views/teacher/login.blade.php
     *
     * ? Route Model Binding:
     * ? {teacher} in URL → Laravel finds Teacher by ID automatically
     * ? /teacher/5 → $teacher is Teacher model with id=5
     *
     * @param  Teacher  $teacher  // * Auto-resolved from URL parameter
     */
    public function showLogin(Teacher $teacher)
    {
        // * Pass teacher to view so we can show their name
        return view('teacher.login', compact('teacher'));
    }

    /**
     * ! ACTION: Process teacher login
     * * Route: POST /teacher/{teacher}/login
     *
     * * Verifies password and creates session
     *
     * @param  TeacherLoginRequest  $request  // * Validates password field
     * @param  Teacher  $teacher  // * The teacher trying to login
     * @param  AuthenticationService  $auth  // * Password verification service
     */
    public function login(TeacherLoginRequest $request, Teacher $teacher, AuthenticationService $auth)
    {
        // * Compare entered password with stored hashed password
        if ($auth->verifyPassword($request->password, $teacher->password)) {
            // ! Security: Regenerate session to prevent fixation attacks
            $request->session()->regenerate();

            // * Store teacher ID in session for authentication
            session(['teacher_id' => $teacher->id]);

            return redirect()->route('teacher.dashboard', $teacher->id);
        }

        // * withErrors() adds error to $errors variable in Blade
        // ? Different from with('error', '...') which uses session flash
        return back()->withErrors(['password' => 'Incorrect password']);
    }

    /**
     * ! ACTION: Teacher Dashboard - Main working area
     * * Route: GET /teacher/{teacher}/dashboard
     * * View: resources/views/teacher/dashboard.blade.php
     *
     * * Shows:
     * * - List of assigned students with stats
     * * - Form to create new lessons
     * * - List of lessons for the current month
     * * - Month navigation
     *
     * @param  Teacher  $teacher  // * The logged-in teacher
     * @param  CalendarService  $calendar  // * Month navigation helper
     * @param  LessonRepository  $lessonRepo  // * Database queries
     * @param  LessonStatisticsService  $statsService  // * Calculate stats
     */
    public function dashboard(Teacher $teacher, CalendarService $calendar, LessonRepository $lessonRepo, LessonStatisticsService $statsService)
    {
        // * Get current month and navigation data
        $calendarData = $calendar->getMonthData(request());
        $date = $calendarData['currentMonth'];         // * Carbon date for current month
        $prevMonth = $calendarData['prevMonth'];       // * Carbon date for previous month
        $nextMonth = $calendarData['nextMonth'];       // * Carbon date for next month

        // * Get only students assigned to THIS teacher
        // * orderBy('name') sorts alphabetically
        $students = $teacher->students()->orderBy('name')->get();

        // * Get lessons for this teacher for the current month
        // * Repository handles eager loading 'student' relationship
        $lessons = $lessonRepo->getForTeacher($teacher->id, $date);

        // * Calculate summary statistics (completed, cancelled, etc.)
        $stats = $statsService->calculateStats($lessons);

        // * Calculate per-student statistics for the stats list
        $studentStats = $statsService->calculateStatsByStudent($lessons);

        // * compact() creates array with same variable names as keys
        // * Same as: ['teacher' => $teacher, 'lessons' => $lessons, ...]
        return view('teacher.dashboard', compact('teacher', 'lessons', 'date', 'stats', 'studentStats', 'students', 'prevMonth', 'nextMonth'));
    }

    /**
     * ! ACTION: Teacher logout
     * * Route: POST /teacher/logout
     *
     * * Destroys session and redirects to home
     */
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget('teacher_id');

        // * Redirect to home page (admin login)
        return redirect('/');
    }

    // ═══════════════════════════════════════════════════════════════════
    // ! LESSON CRUD - JSON API endpoints (called via JavaScript fetch)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * ! ACTION: Update an existing lesson
     * * Route: PUT /lesson/{lesson}
     * * Returns: JSON response
     *
     * ? Why JSON?
     * ? The teacher dashboard uses JavaScript to submit forms
     * ? This allows updates without full page reload
     *
     * @param  UpdateLessonRequest  $request  // * Validates & authorizes
     * @param  Lesson  $lesson  // * The lesson to update
     */
    public function updateLesson(UpdateLessonRequest $request, Lesson $lesson)
    {
        // * Authorization is handled by UpdateLessonRequest::authorize()
        // * It checks: Is this lesson owned by the logged-in teacher?

        // * Get teacher for activity log
        $teacherActor = Teacher::find(session('teacher_id'));

        // * Save original values for audit log
        $original = $lesson->getOriginal();

        // * Update the lesson fields
        $lesson->update([
            'status' => $request->status,
            'topic' => $request->topic ?? '',    // * Default empty if not provided
            'homework' => $request->homework,
            'comments' => $request->comments,
        ]);

        // * Log the update with before/after data
        $this->logActivity(
            $lesson,
            'lesson_updated',
            ['changes' => $lesson->getChanges(), 'original' => $original],
            $teacherActor  // * Actor = who performed the action
        );

        // * Return JSON for JavaScript to handle
        return response()->json(['success' => true, 'lesson' => $lesson]);
    }

    /**
     * ! ACTION: Create a new lesson
     * * Route: POST /teacher/lesson/create
     * * Returns: JSON response
     *
     * * Security checks:
     * * 1. Teacher must be logged in
     * * 2. Student must be assigned to this teacher
     */
    public function createLesson(CreateLessonRequest $request)
    {
        // * Get logged-in teacher from session
        $teacherActor = Teacher::find(session('teacher_id'));

        // ! Security: Verify teacher is logged in
        if (! $teacherActor) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // ! Security: Verify student is assigned to this teacher
        // * This prevents a teacher from creating lessons for other teachers' students
        $assigned = $teacherActor->students()->where('students.id', $request->student_id)->exists();
        if (! $assigned) {
            return response()->json(['error' => 'Student not assigned to teacher'], 403);
        }

        // * Create the new lesson record
        $lesson = Lesson::create([
            'teacher_id' => $teacherActor->id,  // * Always use logged-in teacher's ID
            'student_id' => $request->student_id,
            'class_date' => $request->class_date,
            'status' => $request->status,
            'topic' => $request->topic ?? '',
            'homework' => $request->homework,
            'comments' => $request->comments,
        ]);

        // * Log the creation
        $this->logActivity(
            $lesson,
            'lesson_created',
            [
                'student_id' => $request->student_id,
                'status' => $request->status,
                'class_date' => $request->class_date,
            ],
            $teacherActor
        );

        return response()->json(['success' => true, 'lesson' => $lesson]);
    }

    /**
     * ! ACTION: Delete a lesson
     * * Route: DELETE /lesson/{lesson}
     * * Returns: JSON response
     *
     * ! Teachers can only delete their own lessons
     */
    public function deleteLesson(Lesson $lesson)
    {
        // * Get logged-in teacher
        $teacherActor = Teacher::find(session('teacher_id'));

        // * Save lesson data before deleting (for audit log)
        $snapshot = $lesson->toArray();

        // ! Security: Verify teacher owns this lesson
        if (! $teacherActor || $lesson->teacher_id !== $teacherActor->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // * Soft delete the lesson (sets deleted_at)
        $lesson->delete();

        // * Log with snapshot of what was deleted
        $this->logActivity(
            $lesson,
            'lesson_deleted',
            ['snapshot' => $snapshot],
            $teacherActor
        );

        return response()->json(['success' => true]);
    }
}
