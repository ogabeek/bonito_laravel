<?php

namespace App\Http\Requests;

use App\Enums\LessonStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class LessonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(LessonStatus::values())],
            'topic' => 'required_if:status,' . LessonStatus::COMPLETED->value . '|nullable|string',
            'homework' => 'nullable|string',
            'comments' => 'required_if:status,' . LessonStatus::TEACHER_CANCELLED->value . '|nullable|string',
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
