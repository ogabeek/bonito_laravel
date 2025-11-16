<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LessonForm extends Component
{
    public $lesson;
    public $students;
    public $isNew;

    public function __construct($lesson = null, $students = null)
    {
        $this->lesson = $lesson;
        // Use provided students or fall back to getting teacher's students from session
        if ($students !== null) {
            $this->students = $students;
        } else {
            // Get students for the logged-in teacher
            $teacherId = session('teacher_id');
            if ($teacherId) {
                $teacher = \App\Models\Teacher::find($teacherId);
                $this->students = $teacher ? $teacher->students()->orderBy('name')->get() : collect();
            } else {
                $this->students = collect();
            }
        }
        $this->isNew = is_null($lesson);
    }

    public function render()
    {
        return view('components.lesson-form');
    }
}
