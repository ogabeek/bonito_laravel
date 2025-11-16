<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Lesson;
use App\Services\LessonService;
use App\Http\Requests\CreateLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;


class TeacherController extends Controller
{
    protected $lessonService;

    public function __construct(LessonService $lessonService)
    {
        $this->lessonService = $lessonService;
    }

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
        
        // Use service to get lessons and stats
        $lessons = $this->lessonService->getLessonsForMonth($teacherId, $date);
        $lessonsByWeek = $this->lessonService->groupLessonsByWeek($lessons);
        $stats = $this->lessonService->calculateStats($lessons);
        
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
        $updatedLesson = $this->lessonService->updateLesson($lesson, $request->validated());
        
        return response()->json(['success' => true, 'lesson' => $updatedLesson]);
    }
    // Create new lesson
    public function createLesson(CreateLessonRequest $request)
    {
        $data = array_merge($request->validated(), [
            'teacher_id' => session('teacher_id')
        ]);
        
        $lesson = $this->lessonService->createLesson($data);
        
        return response()->json(['success' => true, 'lesson' => $lesson]);
    }

    // Delete lesson
    public function deleteLesson(Lesson $lesson)
    {
        // Authorization check via UpdateLessonRequest or manual check
        if (!$this->lessonService->teacherOwnsLesson(session('teacher_id'), $lesson)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $lesson->delete();
        
        return response()->json(['success' => true]);
    }
}
