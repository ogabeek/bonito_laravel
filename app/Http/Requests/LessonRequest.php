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
            // * Comments required when teacher cancels OR student is absent
            'comments' => 'required_if:status,'.LessonStatus::TEACHER_CANCELLED->value.','.LessonStatus::STUDENT_ABSENT->value.'|nullable|string',
            'absence_reminder_sent' => 'boolean',
            'absence_chat_notified' => 'boolean',
            'refund_requested' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'topic.required_if' => 'Topic is required for completed lessons.',
            'comments.required_if' => 'A note is required when cancelling a lesson or marking a student absent.',
        ];
    }
}
