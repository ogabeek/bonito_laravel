<?php

namespace App\Http\Requests;

use App\Enums\LessonStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * * BASE REQUEST: Shared lesson validation (abstract)
 * ? Extended by CreateLessonRequest & UpdateLessonRequest
 */
abstract class LessonRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(LessonStatus::values())],
            // * Topic required only for completed lessons
            'topic' => 'required_if:status,'.LessonStatus::COMPLETED->value.'|nullable|string',
            'homework' => 'nullable|string',
            // * Comments required only when teacher cancels
            'comments' => 'required_if:status,'.LessonStatus::TEACHER_CANCELLED->value.'|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'topic.required_if' => 'Topic is required for completed lessons.',
            'comments.required_if' => 'Reason is required when cancelling a lesson.',
        ];
    }
}
