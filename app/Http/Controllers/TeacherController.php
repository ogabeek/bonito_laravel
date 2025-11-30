<?php

namespace App\Http\Controllers;

use App\Enums\LessonStatus;
use App\Models\Teacher;
use App\Models\Lesson;
use App\Concerns\LogsActivityActions;
use App\Services\CalendarService;
use App\Services\LessonStatisticsService;
use App\Repositories\LessonRepository;
use App\Http\Requests\CreateLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;


class TeacherController extends Controller
{
    use LogsActivityActions;

    // Show login form
    public function showLogin(Teacher $teacher)
    {
        return view('teacher.login', compact('teacher'));
    }

    // Handle login
    public function login(Request $request, Teacher $teacher)
    {
        $request->validate([
            'password' => 'required|string|min:4',
        ]);

        $storedPassword = $teacher->password;
        $isHashed = Hash::info($storedPassword)['algo'] !== null;
        $valid = $isHashed
            ? Hash::check($request->password, $storedPassword)
            : hash_equals($storedPassword, $request->password);

        if ($valid) {
            // Upgrade legacy plain-text passwords transparently
            if (!$isHashed) {
                $teacher->update(['password' => Hash::make($request->password)]);
            }

            $request->session()->regenerate();
            session(['teacher_id' => $teacher->id]);
            return redirect()->route('teacher.dashboard', $teacher->id);
        }

        return back()->withErrors(['password' => 'Incorrect password']);
    }

    // Show dashboard
    public function dashboard(Teacher $teacher, CalendarService $calendar, LessonRepository $lessonRepo, LessonStatisticsService $statsService)
    {
        // Get calendar data
        $calendarData = $calendar->getMonthData(request());
        $date = $calendarData['currentMonth'];
        $prevMonth = $calendarData['prevMonth'];
        $nextMonth = $calendarData['nextMonth'];

        // Get only students assigned to this teacher
        $students = $teacher->students()->orderBy('name')->get();

        // Get lessons for this month (newest first)
        $lessons = $lessonRepo->getForTeacher($teacher->id, $date);

        // Calculate stats
        $stats = $statsService->calculateStats($lessons);
        $studentStats = $statsService->calculateStatsByStudent($lessons);

        return view('teacher.dashboard', compact('teacher', 'lessons', 'date', 'stats', 'studentStats', 'students', 'prevMonth', 'nextMonth'));
    }

    // Logout
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget('teacher_id');
        return redirect('/');
    }

     // Update lesson
    public function updateLesson(UpdateLessonRequest $request, Lesson $lesson)
    {
        $teacherActor = Teacher::find(session('teacher_id'));
        $original = $lesson->getOriginal();

        $lesson->update([
            'status' => $request->status,
            'topic' => $request->topic ?? '',
            'homework' => $request->homework,
            'comments' => $request->comments,
        ]);

        $this->logActivity(
            $lesson,
            'lesson_updated',
            ['changes' => $lesson->getChanges(), 'original' => $original],
            $teacherActor
        );
        
        return response()->json(['success' => true, 'lesson' => $lesson->fresh()]);
    }
    
    // Create new lesson
    public function createLesson(CreateLessonRequest $request)
    {
        $teacherActor = Teacher::find(session('teacher_id'));

        $lesson = Lesson::create([
            'teacher_id' => session('teacher_id'),
            'student_id' => $request->student_id,
            'class_date' => $request->class_date,
            'status' => $request->status,
            'topic' => $request->topic ?? '',
            'homework' => $request->homework,
            'comments' => $request->comments,
        ]);

        $this->logActivity(
            $lesson,
            'lesson_created',
            [
                'student_id' => $request->student_id,
                'status' => $request->status,
                'class_date' => $request->class_date,
            ],
            $teacherActor
        );
        
        return response()->json(['success' => true, 'lesson' => $lesson]);
    }

    // Delete lesson
    public function deleteLesson(Lesson $lesson)
    {
        $teacherActor = Teacher::find(session('teacher_id'));
        $snapshot = $lesson->toArray();

        // Check teacher owns this lesson
        if ($lesson->teacher_id !== session('teacher_id')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $lesson->delete();

        $this->logActivity(
            $lesson,
            'lesson_deleted',
            ['snapshot' => $snapshot],
            $teacherActor
        );
        
        return response()->json(['success' => true]);
    }
}
