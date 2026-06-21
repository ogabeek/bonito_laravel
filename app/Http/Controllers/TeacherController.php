<?php

namespace App\Http\Controllers;

use App\Concerns\LogsActivityActions;
use App\Http\Requests\CreateLessonRequest;
use App\Http\Requests\TeacherLoginRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TeacherController - Teacher portal
 *
 * Each teacher has unique URL (/teacher/{id}) for password-only auth.
 */
class TeacherController extends Controller
{
    use LogsActivityActions;

    public function showLogin(Teacher $teacher)
    {
        return view('teacher.login', compact('teacher'));
    }

    public function login(TeacherLoginRequest $request, Teacher $teacher, AuthenticationService $auth)
    {
        if ($auth->verifyPassword($request->password, $teacher->password)) {
            $request->session()->regenerate();
            session(['teacher_id' => $teacher->id]);

            return redirect()->route('teacher.dashboard', $teacher->id);
        }

        return back()->withErrors(['password' => 'Incorrect PIN']);
    }

    public function dashboard(Teacher $teacher): View
    {
        return view('teacher.dashboard', compact('teacher'));
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget('teacher_id');

        return redirect('/');
    }

    // ═══════════════════════════════════════════════════════════════════
    // LESSON CRUD - JSON API endpoints (called via JavaScript)
    // ═══════════════════════════════════════════════════════════════════

    public function updateLesson(UpdateLessonRequest $request, Lesson $lesson)
    {
        $teacherActor = Teacher::find(session('teacher_id'));
        $original = $lesson->getOriginal();
        $isAbsent = $request->status === \App\Enums\LessonStatus::STUDENT_ABSENT->value;

        $lesson->update([
            'status' => $request->status,
            'topic' => $request->topic ?? '',
            'homework' => $request->homework,
            'comments' => $request->comments,
            'absence_reminder_sent' => $isAbsent && $request->boolean('absence_reminder_sent'),
            'absence_chat_notified' => $isAbsent && $request->boolean('absence_chat_notified'),
            'refund_requested' => $isAbsent && $request->boolean('refund_requested'),
        ]);

        // Load student for activity log
        $lesson->load('student');

        $this->logActivity(
            $lesson,
            'lesson_updated',
            ['changes' => $lesson->getChanges(), 'original' => $original],
            $teacherActor
        );

        return response()->json(['success' => true, 'lesson' => $lesson]);
    }

    public function createLesson(CreateLessonRequest $request)
    {
        $teacherActor = Teacher::find(session('teacher_id'));

        if (! $teacherActor) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify student is assigned to this teacher
        $assigned = $teacherActor->students()->where('students.id', $request->student_id)->exists();
        if (! $assigned) {
            return response()->json(['error' => 'Student not assigned to teacher'], 403);
        }

        $isAbsent = $request->status === \App\Enums\LessonStatus::STUDENT_ABSENT->value;

        $lesson = Lesson::create([
            'teacher_id' => $teacherActor->id,
            'student_id' => $request->student_id,
            'class_date' => $request->class_date,
            'status' => $request->status,
            'topic' => $request->topic ?? '',
            'homework' => $request->homework,
            'comments' => $request->comments,
            'absence_reminder_sent' => $isAbsent && $request->boolean('absence_reminder_sent'),
            'absence_chat_notified' => $isAbsent && $request->boolean('absence_chat_notified'),
            'refund_requested' => $isAbsent && $request->boolean('refund_requested'),
        ]);

        // Load student for activity log
        $lesson->load('student');

        $this->logActivity(
            $lesson,
            'lesson_created',
            [
                'student_id' => $request->student_id,
                'class_date' => $request->class_date,
                'status' => $request->status,
                'topic' => $request->topic,
                'homework' => $request->homework,
                'comments' => $request->comments,
            ],
            $teacherActor
        );

        return response()->json(['success' => true, 'lesson' => $lesson]);
    }

    public function deleteLesson(Lesson $lesson)
    {
        $teacherActor = Teacher::find(session('teacher_id'));
        $snapshot = $lesson->toArray();

        if (! $teacherActor || $lesson->teacher_id !== $teacherActor->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Load student for activity log before deleting
        $lesson->load('student');

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
