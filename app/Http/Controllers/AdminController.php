<?php

namespace App\Http\Controllers;

use App\Concerns\LogsActivityActions;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\CreateStudentRequest;
use App\Http\Requests\CreateTeacherRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Repositories\LessonRepository;
use App\Services\AuthenticationService;
use App\Services\BillingDataService;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

/**
 * ! CONTROLLER: AdminController - The Admin "Control Center"
 *
 * * This controller handles ALL admin functionality:
 * * - Authentication (login/logout)
 * * - Dashboard with calendar view
 * * - Billing/stats pages
 * * - Teacher management (CRUD)
 * * - Student management (CRUD)
 * * - Activity logs
 *
 * ? What is a Controller in Laravel?
 * ? Controllers receive HTTP requests and return responses
 * ? They are the "middle-man" between Routes and Views/Data
 *
 * * Request Flow:
 * * ┌──────────┐    ┌─────────┐    ┌────────────┐    ┌──────┐
 * * │ Browser  │ → │ Routes  │ → │ Controller │ → │ View │
 * * │ Request  │    │ web.php │    │ + Services │    │ HTML │
 * * └──────────┘    └─────────┘    └────────────┘    └──────┘
 *
 * ? What is Dependency Injection?
 * ? Look at method parameters like: dashboard(Request $request, BillingDataService $billingService)
 * ? Laravel automatically creates and passes these objects!
 * ? You just declare what you need, Laravel provides it.
 */
class AdminController extends Controller
{
    /**
     * * Trait for logging user actions (creates audit trail)
     * * Adds: $this->logActivity($model, 'action_name', $data)
     */
    use LogsActivityActions;

    /**
     * ! ACTION: Show admin login form
     * * Route: GET /admin/login
     * * View: resources/views/admin/login.blade.php
     *
     * * If already logged in, redirects to dashboard (no need to login again)
     */
    public function showLogin()
    {
        // ? session() is Laravel's session helper
        // ? Sessions store data between requests (like "logged in" state)
        if (session('admin_authenticated')) {
            // * redirect()->route() generates URL from route name
            return redirect()->route('admin.dashboard');
        }

        // * view() renders a Blade template
        return view('admin.login');
    }

    /**
     * ! ACTION: Process admin login form submission
     * * Route: POST /admin/login
     *
     * ? What is AdminLoginRequest?
     * ? It's a Form Request class that validates input BEFORE this method runs
     * ? If validation fails, user is redirected back with errors automatically
     *
     * @param  AdminLoginRequest  $request  // * Validated request (has ->password)
     * @param  AuthenticationService  $auth  // * Service for password verification
     */
    public function login(AdminLoginRequest $request, AuthenticationService $auth)
    {
        // * config() reads from config files (config/app.php)
        // * Admin password is stored in .env file, read via config
        $configuredPassword = config('app.admin_password');

        // ! Safety check: ensure password is configured
        if (empty($configuredPassword)) {
            // * back() returns to previous page
            // * with('error', '...') flashes message to session
            return back()->with('error', 'Admin password is not configured.');
        }

        // * Verify entered password against configured password
        if ($auth->verifyPassword($request->password, (string) $configuredPassword)) {
            // ! Security: Regenerate session ID to prevent session fixation attacks
            $request->session()->regenerate();

            // * Store login state in session
            session(['admin_authenticated' => true]);

            return redirect()->route('admin.dashboard');
        }

        return back()->with('error', 'Invalid password');
    }

    /**
     * ! ACTION: Log out admin user
     * * Route: POST /admin/logout
     *
     * * Destroys session and redirects to login page
     */
    public function logout(Request $request)
    {
        // * invalidate() destroys all session data
        $request->session()->invalidate();

        // ! Security: Generate new CSRF token
        $request->session()->regenerateToken();

        // * Remove specific session key
        session()->forget('admin_authenticated');

        return redirect()->route('admin.login')->with('success', 'Logged out successfully');
    }

    /**
     * ! ACTION: Admin Dashboard - Main overview page
     * * Route: GET /admin/dashboard
     * * View: resources/views/admin/dashboard.blade.php
     *
     * * This page shows:
     * * - Calendar grid with lessons per student per day
     * * - Teacher list with stats
     * * - Student list with balance info
     * * - Quick actions (add teacher, add student)
     *
     * @param  Request  $request  // * Has query params like ?year=2024&month=1
     * @param  BillingDataService  $billing  // * Builds all the stats/data
     * @param  CalendarService  $calendar  // * Month navigation helper
     * @param  LessonRepository  $lessonRepo  // * Database queries for lessons
     */
    public function dashboard(Request $request, BillingDataService $billingService, CalendarService $calendar, LessonRepository $lessonRepo)
    {
        // * BillingDataService->build() returns array with:
        // * teachers, students, currentMonth, stats, etc.
        $data = $billingService->build($request);

        // * Add calendar-specific data (days in month, month start date)
        $calendarData = $calendar->getMonthData($request);
        $data['daysInMonth'] = $calendarData['daysInMonth'];
        $data['monthStart'] = $calendarData['monthStart'];

        // * Get all lessons for this month with teacher & student eager loaded
        // * ['teacher', 'student'] prevents N+1 query problem
        $monthLessons = $lessonRepo->getForMonth($data['currentMonth'], ['teacher', 'student']);

        // * Group lessons by "studentId_date" for calendar cell lookup
        // * Example key: "5_2024-01-15" → lessons for student 5 on Jan 15
        $data['lessonsThisMonth'] = $monthLessons->groupBy(function ($lesson) {
            return $lesson->student_id.'_'.$lesson->class_date->format('Y-m-d');
        });

        // * Summary statistics for page header
        $data['stats'] = [
            'teachers' => $data['teachers']->count(),
            'students' => $data['students']->count(),
            'lessons_this_month' => $monthLessons->count(),
        ];

        // * Get soft-deleted teachers for "restore" functionality
        // * onlyTrashed() returns only deleted records
        $data['archivedTeachers'] = Teacher::onlyTrashed()->get();

        // * compact() creates ['data' => $data] for view
        return view('admin.dashboard', $data);
    }

    /**
     * ! ACTION: Billing/Stats page - Detailed financial view
     * * Route: GET /admin/billing
     *
     * * Shows more detailed stats than dashboard
     * * Can toggle between calendar month and billing period (26th-25th)
     */
    public function billing(Request $request, BillingDataService $billingService)
    {
        return view('admin.billing', $billingService->build($request));
    }

    /**
     * ! ACTION: Export billing stats to Google Sheets
     * * Route: POST /admin/billing/export
     *
     * * Sends current month's stats to external Google Sheet
     */
    public function exportBilling(Request $request, BillingDataService $billingService, \App\Services\StatsExportService $exporter)
    {
        $data = $billingService->build($request);
        $exported = $exporter->export($data);

        // * Redirect back to billing page with success/error message
        return redirect()
            ->route('admin.billing', [
                'billing' => $data['billing'] ? 1 : null,
                'year' => $data['currentMonth']->year,
                'month' => $data['currentMonth']->month,
            ])
            ->with($exported ? 'success' : 'error', $exported ? 'Stats exported to sheet' : 'Failed to export stats');
    }

    // ═══════════════════════════════════════════════════════════════════
    // ! TEACHER MANAGEMENT CRUD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * ! ACTION: Create a new teacher
     * * Route: POST /admin/teachers
     *
     * @param  CreateTeacherRequest  $request  // * Validates name & password
     * @param  AuthenticationService  $auth  // * Hashes password
     */
    public function createTeacher(CreateTeacherRequest $request, AuthenticationService $auth)
    {
        // * Create teacher with hashed password
        $teacher = Teacher::create([
            'name' => $request->name,
            'password' => $auth->hash($request->password),  // ! Never store plain passwords!
        ]);

        // * Log this action for audit trail
        $this->logActivity($teacher, 'teacher_created');

        // * Return with login URL for the new teacher
        return redirect()->route('admin.dashboard')->with('success', "Teacher created! URL: {$request->getSchemeAndHttpHost()}/teacher/{$teacher->id}");
    }

    /**
     * ! ACTION: Archive (soft delete) a teacher
     * * Route: DELETE /admin/teachers/{teacher}
     *
     * * Teacher is not permanently deleted - can be restored later
     * * Their lessons and history are preserved
     */
    public function deleteTeacher(Teacher $teacher)
    {
        // * SoftDeletes: Sets deleted_at instead of removing row
        $teacher->delete();
        $this->logActivity($teacher, 'teacher_archived');

        return redirect()->route('admin.dashboard')->with('success', 'Teacher archived successfully!');
    }

    /**
     * ! ACTION: Restore an archived teacher
     * * Route: POST /admin/teachers/{teacher}/restore
     *
     * @param  int  $teacher  // * Note: Using int because soft-deleted models
     *                        // * aren't found by default route model binding
     */
    public function restoreTeacher(int $teacher)
    {
        // * withTrashed() includes soft-deleted records
        $teacherModel = Teacher::withTrashed()->findOrFail($teacher);

        // * restore() clears deleted_at, making teacher active again
        $teacherModel->restore();
        $this->logActivity($teacherModel, 'teacher_restored');

        return redirect()->route('admin.dashboard')->with('success', 'Teacher restored successfully!');
    }

    // ═══════════════════════════════════════════════════════════════════
    // ! STUDENT MANAGEMENT CRUD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * ! ACTION: Create a new student
     * * Route: POST /admin/students
     *
     * * UUID is auto-generated by Student model boot() method
     */
    public function createStudent(CreateStudentRequest $request)
    {
        // * validated() returns only the validated fields from the request
        $student = Student::create($request->validated());

        $this->logActivity($student, 'student_created');

        return redirect()->route('admin.dashboard')->with('success', 'Student created successfully!');
    }

    /**
     * ! ACTION: Show student edit form
     * * Route: GET /admin/students/{student}/edit
     *
     * ? Route Model Binding:
     * ? {student} in URL is automatically converted to Student model
     * ? Laravel uses Student::getRouteKeyName() (uuid) to find the record
     */
    public function editStudentForm(Student $student)
    {
        // * Eager load teachers relationship to prevent N+1
        $student->load('teachers');

        // * Get IDs of already-assigned teachers
        $assignedTeacherIds = $student->teachers->pluck('id')->toArray();

        // * Get teachers NOT yet assigned (for the dropdown)
        $availableTeachers = Teacher::whereNotIn('id', $assignedTeacherIds)->get();

        return view('admin.students.edit', compact('student', 'availableTeachers'));
    }

    /**
     * ! ACTION: Update student information
     * * Route: PUT /admin/students/{student}
     */
    public function updateStudent(UpdateStudentRequest $request, Student $student)
    {
        // * Save original values for audit log comparison
        $original = $student->getOriginal();

        // * Update with validated data
        $student->update($request->validated());

        // * Log what changed
        $this->logActivity(
            $student,
            'student_updated',
            ['changes' => $student->getChanges(), 'original' => $original]
        );

        return redirect()->route('admin.students.edit', $student)->with('success', 'Student updated successfully!');
    }

    /**
     * ! ACTION: Update just the student's status
     * * Route: PUT /admin/students/{student}/status
     *
     * * Separate action because status is changed from dashboard dropdown
     */
    public function updateStudentStatus(Request $request, Student $student)
    {
        // * Inline validation (no Form Request needed for simple case)
        $request->validate([
            'status' => ['required', Rule::in(\App\Enums\StudentStatus::values())],
        ]);

        $original = $student->status;
        $student->update(['status' => $request->status]);

        $this->logActivity(
            $student,
            'student_status_updated',
            ['from' => $original, 'to' => $request->status]
        );

        // * back() returns to the page they came from
        return back()->with('success', 'Student status updated successfully!');
    }

    /**
     * ! ACTION: Assign a teacher to a student
     * * Route: POST /admin/students/{student}/teacher
     *
     * * Uses many-to-many pivot table (student_teacher)
     */
    public function assignTeacherToStudent(Request $request, Student $student)
    {
        $request->validate(['teacher_id' => 'required|exists:teachers,id']);

        // * syncWithoutDetaching() adds without removing existing relationships
        // * Prevents duplicate pivot entries (handles unique constraint)
        $student->teachers()->syncWithoutDetaching([$request->teacher_id]);

        $this->logActivity(
            $student,
            'student_teacher_assigned',
            ['teacher_id' => $request->teacher_id]
        );

        return back()->with('success', 'Teacher assigned successfully!');
    }

    /**
     * ! ACTION: Remove teacher from student
     * * Route: DELETE /admin/students/{student}/teachers/{teacher}
     */
    public function unassignStudent(Student $student, Teacher $teacher)
    {
        // * detach() removes the pivot table row
        $teacher->students()->detach($student->id);

        $this->logActivity(
            $student,
            'student_teacher_unassigned',
            ['teacher_id' => $teacher->id]
        );

        return back()->with('success', 'Student unassigned successfully!');
    }

    /**
     * ! ACTION: View activity logs
     * * Route: GET /admin/logs
     *
     * * Shows recent system activity (who did what, when)
     */
    public function logs()
    {
        // * Activity model is from spatie/laravel-activitylog package
        // * with('subject') eager loads the related model (Student, Teacher, etc.)
        $logs = Activity::latest()->with('subject')->limit(200)->get();

        return view('admin.logs', compact('logs'));
    }
}
