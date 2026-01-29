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

class AdminController extends Controller
{
    use LogsActivityActions;

    // Show login form
    public function showLogin()
    {
        // If already logged in, redirect to dashboard
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    // Handle login
    public function login(AdminLoginRequest $request, AuthenticationService $auth)
    {
        $configuredPassword = config('app.admin_password');

        if (empty($configuredPassword)) {
            return back()->with('error', 'Admin password is not configured.');
        }

        if ($auth->verifyPassword($request->password, (string) $configuredPassword)) {
            $request->session()->regenerate();
            session(['admin_authenticated' => true]);

            return redirect()->route('admin.dashboard');
        }

        return back()->with('error', 'Invalid password');
    }

    // Handle logout
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget('admin_authenticated');

        return redirect()->route('admin.login')->with('success', 'Logged out successfully');
    }

    // Dashboard
    public function dashboard(Request $request, BillingDataService $billingService, CalendarService $calendar, LessonRepository $lessonRepo)
    {
        // Get common billing/stats data
        $data = $billingService->build($request);

        // Add calendar-specific data for dashboard display
        $calendarData = $calendar->getMonthData($request);
        $data['daysInMonth'] = $calendarData['daysInMonth'];
        $data['monthStart'] = $calendarData['monthStart'];

        // Get lessons grouped by student+date for calendar display
        $monthLessons = $lessonRepo->getForMonth($data['currentMonth'], ['teacher', 'student']);
        $data['lessonsThisMonth'] = $monthLessons->groupBy(function ($lesson) {
            return $lesson->student_id.'_'.$lesson->class_date->format('Y-m-d');
        });

        // Summary stats for header
        $data['stats'] = [
            'teachers' => $data['teachers']->count(),
            'students' => $data['students']->count(),
            'lessons_this_month' => $monthLessons->count(),
        ];

        // Archived teachers for restore functionality
        $data['archivedTeachers'] = Teacher::onlyTrashed()->get();

        return view('admin.dashboard', $data);
    }

    public function billing(Request $request, BillingDataService $billingService)
    {
        return view('admin.billing', $billingService->build($request));
    }

    public function exportBilling(Request $request, BillingDataService $billingService, \App\Services\StatsExportService $exporter)
    {
        $data = $billingService->build($request);
        $exported = $exporter->export($data);

        return redirect()
            ->route('admin.billing', [
                'billing' => $data['billing'] ? 1 : null,
                'year' => $data['currentMonth']->year,
                'month' => $data['currentMonth']->month,
            ])
            ->with($exported ? 'success' : 'error', $exported ? 'Stats exported to sheet' : 'Failed to export stats');
    }

    // Teachers Management
    public function createTeacher(CreateTeacherRequest $request, AuthenticationService $auth)
    {
        $teacher = Teacher::create([
            'name' => $request->name,
            'password' => $auth->hash($request->password),
        ]);

        $this->logActivity($teacher, 'teacher_created');

        return redirect()->route('admin.dashboard')->with('success', "Teacher created! URL: {$request->getSchemeAndHttpHost()}/teacher/{$teacher->id}");
    }

    public function deleteTeacher(Teacher $teacher)
    {
        $teacher->delete();
        $this->logActivity($teacher, 'teacher_archived');

        return redirect()->route('admin.dashboard')->with('success', 'Teacher archived successfully!');
    }

    public function restoreTeacher(int $teacher)
    {
        $teacherModel = Teacher::withTrashed()->findOrFail($teacher);
        $teacherModel->restore();
        $this->logActivity($teacherModel, 'teacher_restored');

        return redirect()->route('admin.dashboard')->with('success', 'Teacher restored successfully!');
    }

    // Students Management
    public function createStudent(CreateStudentRequest $request)
    {
        $student = Student::create($request->validated());

        $this->logActivity($student, 'student_created');

        return redirect()->route('admin.dashboard')->with('success', 'Student created successfully!');
    }

    public function editStudentForm(Student $student)
    {
        $student->load('teachers');
        $assignedTeacherIds = $student->teachers->pluck('id')->toArray();
        $availableTeachers = Teacher::whereNotIn('id', $assignedTeacherIds)->get();

        return view('admin.students.edit', compact('student', 'availableTeachers'));
    }

    public function updateStudent(UpdateStudentRequest $request, Student $student)
    {
        $original = $student->getOriginal();
        $student->update($request->validated());

        $this->logActivity(
            $student,
            'student_updated',
            ['changes' => $student->getChanges(), 'original' => $original]
        );

        return redirect()->route('admin.students.edit', $student)->with('success', 'Student updated successfully!');
    }

    public function updateStudentStatus(Request $request, Student $student)
    {
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

        return back()->with('success', 'Student status updated successfully!');
    }

    public function assignTeacherToStudent(Request $request, Student $student)
    {
        $request->validate(['teacher_id' => 'required|exists:teachers,id']);

        // Avoid duplicate pivot insert (unique constraint)
        $student->teachers()->syncWithoutDetaching([$request->teacher_id]);

        $this->logActivity(
            $student,
            'student_teacher_assigned',
            ['teacher_id' => $request->teacher_id]
        );

        return back()->with('success', 'Teacher assigned successfully!');
    }

    public function unassignStudent(Student $student, Teacher $teacher)
    {
        $teacher->students()->detach($student->id);

        $this->logActivity(
            $student,
            'student_teacher_unassigned',
            ['teacher_id' => $teacher->id]
        );

        return back()->with('success', 'Student unassigned successfully!');
    }

    public function logs()
    {
        $logs = Activity::latest()->with('subject')->limit(200)->get();

        return view('admin.logs', compact('logs'));
    }
}
