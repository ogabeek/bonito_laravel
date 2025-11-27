<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Lesson;
use App\Http\Requests\CreateLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;


class TeacherController extends Controller
{
    // Show login form
    public function showLogin($teacherId)
    {
        $teacher = Teacher::findOrFail($teacherId);
        return view('teacher.login', compact('teacher'));
    }

    // Handle login
    public function login(Request $request, $teacherId)
    {
        $teacher = Teacher::findOrFail($teacherId);
        
        // Simple password check
        if ($request->password === $teacher->password) {
            // Store teacher ID in session
            session(['teacher_id' => $teacher->id]);
            return redirect()->route('teacher.dashboard', $teacher->id);
        }
        
        return back()->withErrors(['password' => 'Incorrect password']);
    }

    // Show dashboard
    public function dashboard($teacherId)
    {
        // Check if logged in
        if (session('teacher_id') != $teacherId) {
            return redirect()->route('teacher.login', $teacherId);
        }

        $teacher = Teacher::findOrFail($teacherId);
        
        // Get current month from request or use current month
        $month = request('month', now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');
        
        // Get only students assigned to this teacher
        $students = $teacher->students()->orderBy('name')->get();
        
        // Get lessons for this month (newest first)
        $lessons = Lesson::where('teacher_id', $teacherId)
            ->whereYear('class_date', $date->year)
            ->whereMonth('class_date', $date->month)
            ->with('student')
            ->orderBy('class_date', 'desc')
            ->get();
        
        // Group by week (newest week first)
        $lessonsByWeek = $lessons->groupBy(function ($lesson) {
            return $lesson->class_date->startOfWeek()->format('Y-m-d');
        })->sortKeysDesc();
        
        // Calculate stats
        $stats = [
            'total' => $lessons->count(),
            'completed' => $lessons->where('status', 'completed')->count(),
            'student_absent' => $lessons->where('status', 'student_absent')->count(),
            'teacher_cancelled' => $lessons->where('status', 'teacher_cancelled')->count(),
        ];
        
        return view('teacher.dashboard', compact('teacher', 'lessonsByWeek', 'date', 'stats', 'students'));
    }

    // Logout
    public function logout()
    {
        session()->forget('teacher_id');
        return redirect('/');
    }

     // Update lesson
    public function updateLesson(UpdateLessonRequest $request, Lesson $lesson)
    {
        $lesson->update([
            'status' => $request->status,
            'topic' => $request->topic ?? '',
            'homework' => $request->homework,
            'comments' => $request->comments,
        ]);
        
        return response()->json(['success' => true, 'lesson' => $lesson->fresh()]);
    }
    
    // Create new lesson
    public function createLesson(CreateLessonRequest $request)
    {
        $lesson = Lesson::create([
            'teacher_id' => session('teacher_id'),
            'student_id' => $request->student_id,
            'class_date' => $request->class_date,
            'status' => $request->status,
            'topic' => $request->topic ?? '',
            'homework' => $request->homework,
            'comments' => $request->comments,
        ]);
        
        return response()->json(['success' => true, 'lesson' => $lesson]);
    }

    // Delete lesson
    public function deleteLesson(Lesson $lesson)
    {
        // Check teacher owns this lesson
        if ($lesson->teacher_id !== session('teacher_id')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $lesson->delete();
        
        return response()->json(['success' => true]);
    }
}
