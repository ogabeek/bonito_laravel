<?php

namespace App\Http\Controllers;

use App\Enums\LessonStatus;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use App\Services\CalendarService;
use App\Services\LessonStatisticsService;
use App\Http\Requests\CreateStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminController extends Controller
{

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

        $configuredPassword = (string) config('app.admin_password', '');

        $isHashed = Hash::info($configuredPassword)['algo'] !== null;
        $valid = $isHashed
            ? Hash::check($request->password, $configuredPassword)
            : hash_equals($configuredPassword, $request->password);

        if ($configuredPassword !== '' && $valid) {
            session(['admin_authenticated' => true]);
            return redirect()->route('admin.dashboard');
        }

        return back()->with('error', 'Invalid password');
    }

    // Handle logout
    public function logout()
    {
        session()->forget('admin_authenticated');
        return redirect()->route('admin.login')->with('success', 'Logged out successfully');
    }

    // Dashboard
    public function dashboard(Request $request, CalendarService $calendar, LessonStatisticsService $statsService)
    {
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

        // Pre-calculate lesson stats per student for this month (avoids N+1 in Blade)
        $studentLessonStats = $statsService->calculateStatsByStudent($monthLessons);

        $stats = [
            'teachers' => Teacher::count(),
            'students' => Student::count(),
            'lessons_this_month' => $monthLessons->count(),
        ];

        $teachers = Teacher::withFullDetails()->get();
        $students = Student::withFullDetails()->get()->map(function($student) {
            $student->teacher_ids = $student->teachers->pluck('id')->toArray();
            return $student;
        });

        // Get archived (soft-deleted) teachers for restore functionality
        $archivedTeachers = Teacher::onlyTrashed()->get();

        return view('admin.dashboard', compact('stats', 'teachers', 'students', 'currentMonth', 'daysInMonth', 'monthStart', 'lessonsThisMonth', 'prevMonth', 'nextMonth', 'studentLessonStats', 'archivedTeachers'));
    }

    // Teachers Management
    public function createTeacher(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:4',
        ]);

        Teacher::create([
            'name' => $request->name,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Teacher created successfully!');
    }

    public function deleteTeacher(Teacher $teacher)
    {
        $teacher->delete();
        return redirect()->route('admin.dashboard')->with('success', 'Teacher archived successfully!');
    }

    public function restoreTeacher($id)
    {
        $teacher = Teacher::withTrashed()->findOrFail($id);
        $teacher->restore();
        return redirect()->route('admin.dashboard')->with('success', 'Teacher restored successfully!');
    }

    // Students Management
    public function createStudent(CreateStudentRequest $request)
    {
        Student::create($request->validated());

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
        $student->update($request->validated());

        return redirect()->route('admin.students.edit', $student)->with('success', 'Student updated successfully!');
    }

    public function updateStudentStatus(Request $request, Student $student)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', \App\Enums\StudentStatus::values()),
        ]);

        $student->update(['status' => $request->status]);

        return back()->with('success', 'Student status updated successfully!');
    }

    public function assignTeacherToStudent(Request $request, Student $student)
    {
        $request->validate(['teacher_id' => 'required|exists:teachers,id']);

        // Attach teacher to student
        $student->teachers()->attach($request->teacher_id);

        return back()->with('success', 'Teacher assigned successfully!');
    }

    public function unassignStudent(Student $student, Teacher $teacher)
    {
        $teacher->students()->detach($student->id);

        return back()->with('success', 'Student unassigned successfully!');
    }
}
