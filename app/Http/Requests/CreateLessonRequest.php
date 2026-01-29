<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * ! FORM REQUEST: Create Lesson
 * * Purpose: Validates data when teacher creates a new lesson
 * * Why: Ensures teacher can only create lessons for their own students
 * * What: Adds student_id and class_date validation to parent LessonRequest rules
 */
class CreateLessonRequest extends LessonRequest
{
    /**
     * * Authorization: Only logged-in teachers can create lessons
     */
    public function authorize(): bool
    {
        // ! Security: Check if teacher is authenticated
        // ? Teacher ID stored in session after login
        return session()->has('teacher_id');
    }

    /**
     * * Validation Rules: Additional rules for creating lessons
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // * Merge parent rules (status, topic, etc.) with create-specific rules
        return array_merge(parent::rules(), [
            'student_id' => [
                'required',                              // * Student must be selected
                'exists:students,id',                    // * Student must exist in database
                // ! Security: Teacher can only create lessons for THEIR students
                // ? Checks student_teacher pivot table for this teacher-student relationship
                Rule::exists('student_teacher', 'student_id')
                    ->where('teacher_id', session('teacher_id')),
            ],
            // * Date when lesson happened/will happen
            'class_date' => 'required|date',
        ]);
    }
}
