<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
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
    public function dashboard(Request $request)
    {
        // Calendar data - get month from query param or use current month
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $currentMonth = Carbon::createFromDate($year, $month, 1);
        
        $daysInMonth = $currentMonth->daysInMonth;
        $monthStart = $currentMonth->copy()->startOfMonth();
        
        // Previous and next month for navigation
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();
        
        // Get all lessons for selected month grouped by student and date
        $lessonsThisMonth = Lesson::with(['teacher', 'student'])
            ->forMonth($currentMonth)
            ->get()
            ->groupBy(function($lesson) {
                return $lesson->student_id . '_' . $lesson->class_date->format('Y-m-d');
            });
        
        // Pre-calculate lesson stats per student for this month (avoids N+1 in Blade)
        $studentLessonStats = Lesson::forMonth($currentMonth)
            ->get()
            ->groupBy('student_id')
            ->map(function($lessons) {
                return [
                    'total' => $lessons->count(),
                    'completed' => $lessons->where('status', 'completed')->count()
                ];
            });
        
        $stats = [
            'teachers' => Teacher::count(),
            'students' => Student::count(),
            'lessons_this_month' => Lesson::forMonth($currentMonth)->count(),
        ];
        
        $teachers = Teacher::withCount('students', 'lessons')->with('students')->get();
        $students = Student::withCount('teachers', 'lessons')->with('teachers')->get();
        
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
    public function createStudent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'goal' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        Student::create($request->all());

        return redirect()->route('admin.dashboard')->with('success', 'Student created successfully!');
    }

    public function editStudentForm(Student $student)
    {
        return view('admin.students.edit', compact('student'));
    }

    public function updateStudent(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'goal' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $student->update($request->all());

        return redirect()->route('admin.students')->with('success', 'Student updated successfully!');
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

    // Teacher-Student Assignment
    public function assignStudent(Request $request, Teacher $teacher)
    {
        $request->validate(['student_id' => 'required|exists:students,id']);
        
        $teacher->students()->attach($request->student_id);
        
        return back()->with('success', 'Student assigned successfully!');
    }

    public function unassignStudent(Teacher $teacher, Student $student)
    {
        $teacher->students()->detach($student->id);
        
        return back()->with('success', 'Student unassigned successfully!');
    }
}
