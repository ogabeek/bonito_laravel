<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $lesson = $this->route('lesson');
        return session('teacher_id') == $lesson->teacher_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:completed,student_absent,teacher_cancelled',
            'topic' => 'required_if:status,completed|nullable|string',
            'homework' => 'nullable|string',
            'comments' => 'required_if:status,teacher_cancelled|nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'topic.required_if' => 'Topic is required for completed lessons.',
            'comments.required_if' => 'Reason is required when cancelling a lesson.',
        ];
    }
}
