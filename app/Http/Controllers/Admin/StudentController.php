<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\LogsActivityActions;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\BalanceLedgerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin student management (CRUD, status, teacher assignment).
 */
class StudentController extends Controller
{
    use LogsActivityActions;

    public function store(CreateStudentRequest $request)
    {
        $student = Student::create($request->validated());
        $this->logActivity($student, 'student_created');

        return redirect()->route('admin.dashboard')->with('success', 'Student created successfully!');
    }

    public function edit(Student $student, BalanceLedgerService $ledger)
    {
        $student->load('teachers');
        $assignedTeacherIds = $student->teachers->pluck('id')->toArray();
        $availableTeachers = Teacher::whereNotIn('id', $assignedTeacherIds)->get();

        return view('admin.students.edit', [
            'student' => $student,
            'availableTeachers' => $availableTeachers,
            'ledger' => $ledger->forStudent($student),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student)
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

    public function updateStatus(Request $request, Student $student)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(StudentStatus::values())],
        ]);

        $original = $student->status->value;
        $status = StudentStatus::from($validated['status']);
        $student->changeStatus($status);

        $this->logActivity(
            $student,
            'student_status_updated',
            ['from' => $original, 'to' => $status->value]
        );

        return back()->with('success', 'Student status updated successfully!');
    }

    // Pivot: syncWithoutDetaching prevents duplicates
    public function assignTeacher(Request $request, Student $student)
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

    public function unassign(Student $student, Teacher $teacher)
    {
        $teacher->students()->detach($student->id);

        $this->logActivity(
            $student,
            'student_teacher_unassigned',
            ['teacher_id' => $teacher->id]
        );

        return back()->with('success', 'Student unassigned successfully!');
    }
}
