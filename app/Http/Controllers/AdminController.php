<?php

namespace App\Http\Controllers;

use App\Enums\LessonStatus;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Concerns\LogsActivityActions;
use App\Services\CalendarService;
use App\Services\LessonStatisticsService;
use App\Services\BalanceService;
use App\Services\StudentBalanceService;
use App\Services\TeacherStatsService;
use App\Repositories\LessonRepository;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\CreateStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

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
    public function login(AdminLoginRequest $request)
    {
        $configuredPassword = config('app.admin_password');

        if (empty($configuredPassword)) {
            return back()->with('error', 'Admin password is not configured.');
        }

        $isHashed = Hash::info((string) $configuredPassword)['algo'] !== null;
        $valid = $isHashed
            ? Hash::check($request->password, $configuredPassword)
            : hash_equals((string) $configuredPassword, $request->password);

        if ($valid) {
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
    public function dashboard(Request $request, CalendarService $calendar, LessonStatisticsService $statsService, StudentBalanceService $studentBalanceService, TeacherStatsService $teacherStatsService, LessonRepository $lessonRepo)
    {
        $billing = $request->boolean('billing');

        // Get calendar data
        $calendarData = $calendar->getMonthData($request);
        $currentMonth = $calendarData['currentMonth'];
        $prevMonth = $calendarData['prevMonth'];
        $nextMonth = $calendarData['nextMonth'];
        $daysInMonth = $calendarData['daysInMonth'];
        $monthStart = $calendarData['monthStart'];

        // Get all lessons for selected month with relationships
        $monthLessons = $lessonRepo->getForMonth($currentMonth, ['teacher', 'student']);

        // Group lessons by student and date for calendar display
        $lessonsThisMonth = $monthLessons->groupBy(function($lesson) {
            return $lesson->student_id . '_' . $lesson->class_date->format('Y-m-d');
        });

        // Stats period: calendar month or billing (26 -> 25)
        $periodLessons = $lessonRepo->getForPeriod($currentMonth, $billing, ['teacher', 'student']);

        // Load teachers and students once for all operations
        $teachers = Teacher::withFullDetails()->get();
        $students = $studentBalanceService->enrichStudentsWithBalance();

        $periodStats = $statsService->calculateStats($periodLessons);
        $studentStats = $statsService->calculateStatsByStudent($periodLessons);
        $teacherStats = $statsService->calculateStatsByTeacher($periodLessons);
        $teacherStudentCounts = $teacherStatsService->buildTeacherStudentStats($teachers, $periodLessons);

        $yearLessons = $lessonRepo->getForYear($currentMonth->year, ['teacher', 'student']);
        $yearStatsByMonth = $statsService->calculateStatsByMonth($yearLessons);

        $stats = [
            'teachers' => $teachers->count(),
            'students' => $students->count(),
            'lessons_this_month' => $monthLessons->count(),
        ];

        // Get archived (soft-deleted) teachers for restore functionality
        $archivedTeachers = Teacher::onlyTrashed()->get();

        return view('admin.dashboard', compact(
            'stats',
            'teachers',
            'students',
            'currentMonth',
            'daysInMonth',
            'monthStart',
            'lessonsThisMonth',
            'prevMonth',
            'nextMonth',
            'archivedTeachers',
            'periodStats',
            'studentStats',
            'teacherStats',
            'billing',
            'yearStatsByMonth',
            'teacherStudentCounts'
        ));
    }

    public function billing(Request $request, CalendarService $calendar, LessonStatisticsService $statsService, StudentBalanceService $studentBalanceService, TeacherStatsService $teacherStatsService, LessonRepository $lessonRepo)
    {
        $data = $this->buildBillingData($request, $calendar, $statsService, $studentBalanceService, $teacherStatsService, $lessonRepo);
        return view('admin.billing', $data);
    }

    public function exportBilling(Request $request, CalendarService $calendar, LessonStatisticsService $statsService, StudentBalanceService $studentBalanceService, TeacherStatsService $teacherStatsService, LessonRepository $lessonRepo, \App\Services\StatsExportService $exporter)
    {
        $data = $this->buildBillingData($request, $calendar, $statsService, $studentBalanceService, $teacherStatsService, $lessonRepo);
        $exported = $exporter->export($data);

        return redirect()
            ->route('admin.billing', [
                'billing' => $data['billing'] ? 1 : null,
                'year' => $data['currentMonth']->year,
                'month' => $data['currentMonth']->month,
            ])
            ->with($exported ? 'success' : 'error', $exported ? 'Stats exported to sheet' : 'Failed to export stats');
    }

    protected function buildBillingData(Request $request, CalendarService $calendar, LessonStatisticsService $statsService, StudentBalanceService $studentBalanceService, TeacherStatsService $teacherStatsService, LessonRepository $lessonRepo): array
    {
        $billing = $request->boolean('billing');

        $calendarData = $calendar->getMonthData($request);
        $currentMonth = $calendarData['currentMonth'];
        $prevMonth = $calendarData['prevMonth'];
        $nextMonth = $calendarData['nextMonth'];

        // Lessons for period (calendar or billing 26-25)
        $periodLessons = $lessonRepo->getForPeriod($currentMonth, $billing, ['teacher', 'student']);

        // Load teachers and students once for all operations
        $teachers = Teacher::withFullDetails()->get();
        $students = $studentBalanceService->enrichStudentsWithBalance();

        $periodStats = $statsService->calculateStats($periodLessons);
        $studentStats = $statsService->calculateStatsByStudent($periodLessons);
        $teacherStats = $statsService->calculateStatsByTeacher($periodLessons);

        $yearLessons = $lessonRepo->getForYear($currentMonth->year, ['teacher', 'student']);
        $yearStatsByMonth = $statsService->calculateStatsByMonth($yearLessons);
        $studentMonthStats = $yearLessons->groupBy('student_id')->map(function($lessonsForStudent) use ($statsService) {
            return $lessonsForStudent
                ->groupBy(fn($lesson) => (int) $lesson->class_date->format('n'))
                ->map(fn($monthLessons) => $statsService->calculateStats($monthLessons));
        });
        $teacherMonthStats = $yearLessons->groupBy('teacher_id')->map(function($lessonsForTeacher) use ($statsService) {
            return $lessonsForTeacher
                ->groupBy(fn($lesson) => (int) $lesson->class_date->format('n'))
                ->map(fn($monthLessons) => $statsService->calculateStats($monthLessons));
        });
        $months = range(1, 12);

        $teacherStudentCounts = $teacherStatsService->buildTeacherStudentStats($teachers, $periodLessons);

        return compact(
            'teachers',
            'students',
            'currentMonth',
            'prevMonth',
            'nextMonth',
            'periodStats',
            'studentStats',
            'teacherStats',
            'billing',
            'yearStatsByMonth',
            'studentMonthStats',
            'teacherMonthStats',
            'months',
            'teacherStudentCounts'
        );
    }

    // Teachers Management
    public function createTeacher(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:4',
        ]);

        $teacher = Teacher::create([
            'name' => $request->name,
            'password' => Hash::make($request->password),
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

    public function restoreTeacher($id)
    {
        $teacher = Teacher::withTrashed()->findOrFail($id);
        $teacher->restore();
        $this->logActivity($teacher, 'teacher_restored');
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
