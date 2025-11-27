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
use Carbon\Carbon;

class AdminController extends Controller
{

    // Admin password (in production, store this in .env or database)
    private const ADMIN_PASSWORD = 'admin13';

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
            'password' => 'required|string',
        ]);

        if ($request->password === self::ADMIN_PASSWORD) {
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

        return view('admin.dashboard', compact('stats', 'teachers', 'students', 'currentMonth', 'daysInMonth', 'monthStart', 'lessonsThisMonth', 'prevMonth', 'nextMonth', 'studentLessonStats'));
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
            'password' => $request->password,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Teacher created successfully!');
    }

    public function deleteTeacher(Teacher $teacher)
    {
        $teacher->delete();
        return redirect()->route('admin.dashboard')->with('success', 'Teacher deleted successfully!');
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

    public function deleteStudent(Student $student)
    {
        $student->delete();
        return redirect()->route('admin.dashboard')->with('success', 'Student deleted successfully!');
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
