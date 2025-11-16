<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LessonForm extends Component
{
    public $lesson;
    public $students;
    public $isNew;

    public function __construct($lesson = null)
    {
        $this->lesson = $lesson;
        $this->students = \App\Models\Student::orderBy('name')->get();
        $this->isNew = is_null($lesson);
    }

    public function render()
    {
        return view('components.lesson-form');
    }
}
