<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Lesson;
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
        
        // Get all lessons for this teacher in this month
        $lessons = Lesson::where('teacher_id', $teacherId)
            ->whereYear('class_date', $date->year)
            ->whereMonth('class_date', $date->month)
            ->with('student')
            ->orderBy('class_date', 'asc')
            ->get();
        
        // Group lessons by week
        $lessonsByWeek = $lessons->groupBy(function($lesson) {
            return $lesson->class_date->startOfWeek()->format('Y-m-d');
        });
        
        // Calculate monthly stats
        $stats = [
            'total' => $lessons->count(),
            'completed' => $lessons->where('status', 'completed')->count(),
            'student_absent' => $lessons->where('status', 'student_absent')->count(),
            'teacher_cancelled' => $lessons->where('status', 'teacher_cancelled')->count(),
        ];
        
        return view('teacher.dashboard', compact('teacher', 'lessonsByWeek', 'date', 'stats'));
    }

    // Logout
    public function logout()
    {
        session()->forget('teacher_id');
        return redirect('/');
    }

     // Update lesson
    public function updateLesson(Request $request, $lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        
        // Check if this lesson belongs to the logged-in teacher
        if (session('teacher_id') != $lesson->teacher_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $lesson->status = $request->status;
        
        // If marking as completed, require topic
        if ($request->status === 'completed') {
            $request->validate([
                'topic' => 'required|string',
            ]);
            
            $lesson->topic = $request->topic;
            $lesson->homework = $request->homework;
            $lesson->comments = $request->comments;
        }
        
        $lesson->save();
        
        return response()->json(['success' => true, 'lesson' => $lesson]);
    }
        // Create new lesson
    public function createLesson(Request $request)
    {
        // Check if logged in
        if (!session('teacher_id')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_date' => 'required|date',
            'status' => 'required|in:scheduled,completed,student_absent,teacher_cancelled',
            'topic' => 'required_if:status,completed|nullable|string',
            'homework' => 'nullable|string',
            'comments' => 'nullable|string',
        ]);
        
        $lesson = Lesson::create([
            'teacher_id' => session('teacher_id'),
            'student_id' => $validated['student_id'],
            'class_date' => $validated['class_date'],
            'status' => $validated['status'],
            'topic' => $validated['topic'] ?? '',
            'homework' => $validated['homework'] ?? null,
            'comments' => $validated['comments'] ?? null,
        ]);
        
        return response()->json(['success' => true, 'lesson' => $lesson]);
    }
}
