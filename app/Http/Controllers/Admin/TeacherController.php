<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\LogsActivityActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Teacher;
use Illuminate\Support\Arr;

/**
 * Admin teacher management (CRUD + archive/restore).
 */
class TeacherController extends Controller
{
    use LogsActivityActions;

    public function store(CreateTeacherRequest $request)
    {
        $teacher = Teacher::create([
            'name' => $request->name,
            'password' => $request->password,
        ]);

        $this->logActivity($teacher, 'teacher_created');

        return redirect()->route('admin.dashboard')->with('success', "Teacher created! URL: {$request->getSchemeAndHttpHost()}/teacher/{$teacher->id}");
    }

    public function edit(Teacher $teacher)
    {
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher)
    {
        $validated = $request->validated();

        // Blank password on update = keep the current one.
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $original = Arr::except($teacher->getOriginal(), ['password']);
        $teacher->update($validated);

        $this->logActivity(
            $teacher,
            'teacher_updated',
            ['changes' => Arr::except($teacher->getChanges(), ['password']), 'original' => $original]
        );

        return redirect()->route('admin.teachers.edit', $teacher)->with('success', 'Teacher updated successfully!');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();
        $this->logActivity($teacher, 'teacher_archived');

        return redirect()->route('admin.dashboard')->with('success', 'Teacher archived successfully!');
    }

    // Int param because soft-deleted models aren't found by default route model binding
    public function restore(int $teacher)
    {
        $teacherModel = Teacher::withTrashed()->findOrFail($teacher);
        $teacherModel->restore();
        $this->logActivity($teacherModel, 'teacher_restored');

        return redirect()->route('admin.dashboard')->with('success', 'Teacher restored successfully!');
    }
}
