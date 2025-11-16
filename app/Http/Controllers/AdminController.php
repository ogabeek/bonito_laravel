<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminController extends Controller
{
    // Dashboard
    public function dashboard()
    {
        $stats = [
            'teachers' => Teacher::count(),
            'students' => Student::count(),
            'lessons_this_month' => Lesson::whereYear('class_date', now()->year)
                ->whereMonth('class_date', now()->month)
                ->count(),
        ];
        
        return view('admin.dashboard', compact('stats'));
    }

    // Teachers Management
    public function teachers()
    {
        $teachers = Teacher::withCount('students', 'lessons')->get();
        return view('admin.teachers.index', compact('teachers'));
    }

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

        return redirect()->route('admin.teachers')->with('success', 'Teacher created successfully!');
    }

    public function deleteTeacher(Teacher $teacher)
    {
        $teacher->delete();
        return redirect()->route('admin.teachers')->with('success', 'Teacher deleted successfully!');
    }

    // Students Management
    public function students()
    {
        $students = Student::withCount('teachers', 'lessons')->get();
        return view('admin.students.index', compact('students'));
    }

    public function createStudentForm()
    {
        return view('admin.students.create');
    }

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

        return redirect()->route('admin.students')->with('success', 'Student created successfully!');
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
        return redirect()->route('admin.students')->with('success', 'Student deleted successfully!');
    }

    // Teacher-Student Assignment
    public function teacherStudents(Teacher $teacher)
    {
        $assignedStudents = $teacher->students;
        $availableStudents = Student::whereDoesntHave('teachers', function($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })->get();
        
        return view('admin.teachers.students', compact('teacher', 'assignedStudents', 'availableStudents'));
    }

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
