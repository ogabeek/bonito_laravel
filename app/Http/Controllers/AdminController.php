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
use App\Http\Requests\CreateStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:4',
        ]);

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
    public function dashboard(Request $request, CalendarService $calendar, LessonStatisticsService $statsService, BalanceService $balanceService)
    {
        $billing = $request->boolean('billing');

        // Get calendar data
        $calendarData = $calendar->getMonthData($request);
        $currentMonth = $calendarData['currentMonth'];
        $prevMonth = $calendarData['prevMonth'];
        $nextMonth = $calendarData['nextMonth'];
        $daysInMonth = $calendarData['daysInMonth'];
        $monthStart = $calendarData['monthStart'];

        // Get all lessons for selected month with relationships (single query)
        $monthLessons = Lesson::with(['teacher', 'student'])->forMonth($currentMonth)->get();

        // Group lessons by student and date for calendar display
        $lessonsThisMonth = $monthLessons->groupBy(function($lesson) {
            return $lesson->student_id . '_' . $lesson->class_date->format('Y-m-d');
        });

        // Stats period: calendar month or billing (26 -> 25)
        if ($billing) {
            // Billing period spans 26th of previous month to 25th of current month
            $periodStart = $currentMonth->copy()->subMonthNoOverflow()->day(26);
            $periodEnd = $currentMonth->copy()->day(25)->endOfDay();
            $periodLessons = Lesson::with(['teacher', 'student'])
                ->whereBetween('class_date', [$periodStart, $periodEnd])
                ->get();
        } else {
            $periodLessons = $monthLessons;
        }

        $periodStats = $statsService->calculateStats($periodLessons);
        $studentStats = $statsService->calculateStatsByStudent($periodLessons);
        $teacherStats = $statsService->calculateStatsByTeacher($periodLessons);

        $teachers = Teacher::withFullDetails()->get();
        $teacherStudentCounts = $teachers->mapWithKeys(function($teacher) use ($periodLessons, $statsService) {
            $lessonsForTeacher = $periodLessons->where('teacher_id', $teacher->id);
            $byStudent = $lessonsForTeacher
                ->groupBy('student_id')
                ->map(function($studentLessons) use ($statsService) {
                    $student = $studentLessons->first()->student;
                    return [
                        'name' => $student?->name ?? 'Unknown',
                        'stats' => $statsService->calculateStats($studentLessons),
                    ];
                })
                ->sortBy('name')
                ->values();

            return [$teacher->id => $byStudent];
        });

        $yearLessons = Lesson::with(['teacher', 'student'])
            ->whereYear('class_date', $currentMonth->year)
            ->get();
        $yearStatsByMonth = $statsService->calculateStatsByMonth($yearLessons);

        $stats = [
            'teachers' => Teacher::count(),
            'students' => Student::count(),
            'lessons_this_month' => $monthLessons->count(),
        ];

        $teachers = Teacher::withFullDetails()->get();
        $balances = $balanceService->getBalances();

        // Usage counts up to today for chargeable lessons (completed + student_absent)
        $chargeableStatuses = [
            LessonStatus::COMPLETED,
            LessonStatus::STUDENT_ABSENT,
        ];
        $usedCounts = Lesson::whereDate('class_date', '<=', now()->toDateString())
            ->whereIn('status', $chargeableStatuses)
            ->selectRaw('student_id, count(*) as used')
            ->groupBy('student_id')
            ->pluck('used', 'student_id');

        $students = Student::withFullDetails()->get()->map(function($student) use ($balances, $usedCounts) {
            $student->teacher_ids = $student->teachers->pluck('id')->toArray();
            $paid = $balances[$student->uuid] ?? null;
            $used = $usedCounts[$student->id] ?? 0;
            $student->class_balance = $paid !== null ? ($paid - $used) : null;
            return $student;
        });

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
            'yearStatsByMonth'
        ));
    }

    public function billing(Request $request, CalendarService $calendar, LessonStatisticsService $statsService, BalanceService $balanceService)
    {
        $data = $this->buildBillingData($request, $calendar, $statsService, $balanceService);
        return view('admin.billing', $data);
    }

    public function exportBilling(Request $request, CalendarService $calendar, LessonStatisticsService $statsService, BalanceService $balanceService, \App\Services\StatsExportService $exporter)
    {
        $data = $this->buildBillingData($request, $calendar, $statsService, $balanceService);
        $exported = $exporter->export($data);

        return redirect()
            ->route('admin.billing', [
                'billing' => $data['billing'] ? 1 : null,
                'year' => $data['currentMonth']->year,
                'month' => $data['currentMonth']->month,
            ])
            ->with($exported ? 'success' : 'error', $exported ? 'Stats exported to sheet' : 'Failed to export stats');
    }

    protected function buildBillingData(Request $request, CalendarService $calendar, LessonStatisticsService $statsService, BalanceService $balanceService): array
    {
        $billing = $request->boolean('billing');

        $calendarData = $calendar->getMonthData($request);
        $currentMonth = $calendarData['currentMonth'];
        $prevMonth = $calendarData['prevMonth'];
        $nextMonth = $calendarData['nextMonth'];

        // Lessons for period (calendar or billing 26-25)
        if ($billing) {
            $periodStart = $currentMonth->copy()->subMonthNoOverflow()->day(26);
            $periodEnd = $currentMonth->copy()->day(25)->endOfDay();
            $periodLessons = Lesson::with(['teacher', 'student'])
                ->whereBetween('class_date', [$periodStart, $periodEnd])
                ->get();
        } else {
            $periodLessons = Lesson::with(['teacher', 'student'])->forMonth($currentMonth)->get();
        }

        $periodStats = $statsService->calculateStats($periodLessons);
        $studentStats = $statsService->calculateStatsByStudent($periodLessons);
        $teacherStats = $statsService->calculateStatsByTeacher($periodLessons);

        $yearLessons = Lesson::with(['teacher', 'student'])
            ->whereYear('class_date', $currentMonth->year)
            ->get();
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

        $teachers = Teacher::withFullDetails()->get();
        $teacherStudentCounts = $teachers->mapWithKeys(function($teacher) use ($periodLessons, $statsService) {
            $byStudent = $periodLessons
                ->where('teacher_id', $teacher->id)
                ->groupBy('student_id')
                ->map(function($studentLessons) use ($statsService) {
                    $student = $studentLessons->first()->student;
                    return [
                        'name' => $student?->name ?? 'Unknown',
                        'stats' => $statsService->calculateStats($studentLessons),
                    ];
                })
                ->sortBy('name')
                ->values();

            return [$teacher->id => $byStudent];
        });

        $balances = $balanceService->getBalances();

        // Usage counts up to today for chargeable lessons (completed + student_absent)
        $chargeableStatuses = [
            LessonStatus::COMPLETED,
            LessonStatus::STUDENT_ABSENT,
        ];
        $usedCounts = Lesson::whereDate('class_date', '<=', now()->toDateString())
            ->whereIn('status', $chargeableStatuses)
            ->selectRaw('student_id, count(*) as used')
            ->groupBy('student_id')
            ->pluck('used', 'student_id');

        $students = Student::withFullDetails()->get()->map(function($student) use ($balances, $usedCounts) {
            $student->teacher_ids = $student->teachers->pluck('id')->toArray();
            $paid = $balances[$student->uuid] ?? null;
            $used = $usedCounts[$student->id] ?? 0;
            $student->class_balance = $paid !== null ? ($paid - $used) : null;
            return $student;
        });

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

        return redirect()->route('admin.dashboard')->with('success', 'Teacher created successfully!');
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
            'status' => 'required|in:' . implode(',', \App\Enums\StudentStatus::values()),
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
