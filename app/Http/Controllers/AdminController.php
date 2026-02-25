<?php

namespace App\Http\Controllers;

use App\Concerns\LogsActivityActions;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\CreateStudentRequest;
use App\Http\Requests\CreateTeacherRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AuthenticationService;
use App\Services\BillingDataService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

/**
 * AdminController - Admin portal for managing teachers, students, and viewing stats
 */
class AdminController extends Controller
{
    use LogsActivityActions;

    public function showLogin()
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(AdminLoginRequest $request, AuthenticationService $auth)
    {
        $configuredPassword = config('app.admin_password');

        if (empty($configuredPassword)) {
            return back()->with('error', 'Admin password is not configured.');
        }

        if ($auth->verifyPassword($request->password, (string) $configuredPassword)) {
            $request->session()->regenerate();
            session(['admin_authenticated' => true]);

            return redirect()->route('admin.dashboard');
        }

        return back()->with('error', 'Invalid password');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget('admin_authenticated');

        return redirect()->route('admin.login')->with('success', 'Logged out successfully');
    }

    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function billing()
    {
        return view('admin.billing');
    }

    public function exportBilling(Request $request, BillingDataService $billingService, \App\Services\StatsExportService $exporter)
    {
        $data = $billingService->build($request);
        $exported = $exporter->export($data);

        return redirect()
            ->route('admin.billing', [
                'billing' => $data['billing'] ? 1 : null,
                'year' => $data['currentMonth']->year,
                'month' => $data['currentMonth']->month,
            ])
            ->with($exported ? 'success' : 'error', $exported ? 'Stats exported to sheet' : 'Failed to export stats');
    }

    public function refreshBalance(Request $request, \App\Services\BalanceService $balanceService)
    {
        $balanceService->refreshCache();

        return redirect()
            ->route('admin.billing', [
                'billing' => $request->boolean('billing') ? 1 : null,
                'year' => $request->input('year'),
                'month' => $request->input('month'),
            ])
            ->with('success', 'Balance data refreshed from Google Sheets');
    }

    // ═══════════════════════════════════════════════════════════════════
    // TEACHER MANAGEMENT CRUD
    // ═══════════════════════════════════════════════════════════════════

    public function createTeacher(CreateTeacherRequest $request)
    {
        $teacher = Teacher::create([
            'name' => $request->name,
            'password' => $request->password,
        ]);

        $this->logActivity($teacher, 'teacher_created');

        return redirect()->route('admin.dashboard')->with('success', "Teacher created! URL: {$request->getSchemeAndHttpHost()}/teacher/{$teacher->id}");
    }

    public function editTeacherForm(Teacher $teacher)
    {
        $teacher->makeVisible('password');

        return view('admin.teachers.edit', compact('teacher'));
    }

    public function updateTeacher(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:4'],
            'contact' => ['nullable', 'string', 'max:255'],
            'zoom_link' => ['nullable', 'url', 'max:500'],
            'zoom_id' => ['nullable', 'string', 'max:50'],
            'zoom_passcode' => ['nullable', 'string', 'max:50'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $original = $teacher->getOriginal();
        $teacher->update($validated);

        $this->logActivity(
            $teacher,
            'teacher_updated',
            ['changes' => $teacher->getChanges(), 'original' => $original]
        );

        return redirect()->route('admin.teachers.edit', $teacher)->with('success', 'Teacher updated successfully!');
    }

    public function deleteTeacher(Teacher $teacher)
    {
        $teacher->delete();
        $this->logActivity($teacher, 'teacher_archived');

        return redirect()->route('admin.dashboard')->with('success', 'Teacher archived successfully!');
    }

    // Int param because soft-deleted models aren't found by default route model binding
    public function restoreTeacher(int $teacher)
    {
        $teacherModel = Teacher::withTrashed()->findOrFail($teacher);
        $teacherModel->restore();
        $this->logActivity($teacherModel, 'teacher_restored');

        return redirect()->route('admin.dashboard')->with('success', 'Teacher restored successfully!');
    }

    // ═══════════════════════════════════════════════════════════════════
    // STUDENT MANAGEMENT CRUD
    // ═══════════════════════════════════════════════════════════════════

    public function createStudent(CreateStudentRequest $request)
    {
        $student = Student::create($request->validated());
        $this->logActivity($student, 'student_created');

        return redirect()->route('admin.dashboard')->with('success', 'Student created successfully!');
    }

    public function editStudentForm(Student $student)
    {
        $student->load('teachers');
        $assignedTeacherIds = $student->teachers->pluck('id')->toArray();
        $availableTeachers = Teacher::whereNotIn('id', $assignedTeacherIds)->get();

        return view('admin.students.edit', compact('student', 'availableTeachers'));
    }

    public function updateStudent(UpdateStudentRequest $request, Student $student)
    {
        $original = $student->getOriginal();
        $student->update($request->validated());

        $this->logActivity(
            $student,
            'student_updated',
            ['changes' => $student->getChanges(), 'original' => $original]
        );

        return redirect()->route('admin.students.edit', $student)->with('success', 'Student updated successfully!');
    }

    public function updateStudentStatus(Request $request, Student $student)
    {
        $request->validate([
            'status' => ['required', Rule::in(\App\Enums\StudentStatus::values())],
        ]);

        $original = $student->status;
        $student->update(['status' => $request->status]);

        $this->logActivity(
            $student,
            'student_status_updated',
            ['from' => $original, 'to' => $request->status]
        );

        return back()->with('success', 'Student status updated successfully!');
    }

    // Pivot: syncWithoutDetaching prevents duplicates
    public function assignTeacherToStudent(Request $request, Student $student)
    {
        $request->validate(['teacher_id' => 'required|exists:teachers,id']);
        $student->teachers()->syncWithoutDetaching([$request->teacher_id]);

        $this->logActivity(
            $student,
            'student_teacher_assigned',
            ['teacher_id' => $request->teacher_id]
        );

        return back()->with('success', 'Teacher assigned successfully!');
    }

    public function unassignStudent(Student $student, Teacher $teacher)
    {
        $teacher->students()->detach($student->id);

        $this->logActivity(
            $student,
            'student_teacher_unassigned',
            ['teacher_id' => $teacher->id]
        );

        return back()->with('success', 'Student unassigned successfully!');
    }

    public function logs()
    {
        $logs = Activity::latest()
            ->with(['subject', 'causer'])
            ->limit(200)
            ->get();

        return view('admin.logs', compact('logs'));
    }
}
