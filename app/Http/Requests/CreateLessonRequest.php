<?php

namespace App\Http\Requests;

class CreateLessonRequest extends LessonRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return session()->has('teacher_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'student_id' => 'required|exists:students,id',
            'class_date' => 'required|date',
        ]);
    }
}
