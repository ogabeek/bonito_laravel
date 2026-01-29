<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * * REQUEST: Create Lesson validation
 * ! Security: Teacher can only create lessons for their own students
 */
class CreateLessonRequest extends LessonRequest
{
    public function authorize(): bool
    {
        return session()->has('teacher_id');
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'student_id' => [
                'required',
                'exists:students,id',
                // * Checks student_teacher pivot for this relationship
                Rule::exists('student_teacher', 'student_id')
                    ->where('teacher_id', session('teacher_id')),
            ],
            'class_date' => 'required|date',
        ]);
    }
}
