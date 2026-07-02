<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherLoginRequest;
use App\Models\Teacher;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TeacherController - Teacher portal auth & dashboard shell
 *
 * Each teacher has unique URL (/teacher/{id}) for password-only auth.
 * Lesson CRUD lives in the teacher-dashboard Livewire component (LessonService).
 */
class TeacherController extends Controller
{
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
}
