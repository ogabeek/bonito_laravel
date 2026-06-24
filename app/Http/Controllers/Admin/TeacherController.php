<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ArchivesRecords;
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
    use ArchivesRecords, LogsActivityActions;

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
        return $this->archiveRecord($teacher, 'Teacher');
    }

    // Int param because soft-deleted models aren't found by default route model binding
    public function restore(int $teacher)
    {
        $model = Teacher::withTrashed()->findOrFail($teacher);
        $model->restore();

        return $this->restoredRecord($model, 'Teacher');
    }
}
