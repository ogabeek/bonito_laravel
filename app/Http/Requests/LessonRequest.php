<?php

namespace App\Http\Requests;

use App\Enums\LessonStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ! BASE REQUEST: Lesson Validation (Abstract)
 * * Purpose: Shared validation rules for creating/updating lessons
 * * Why: DRY principle - common lesson validation in one place
 * * What: Validates lesson status, topic, homework, and comments
 * ? Abstract = extended by CreateLessonRequest & UpdateLessonRequest
 */
abstract class LessonRequest extends FormRequest
{
    /**
     * * Validation Rules: What makes a valid lesson?
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // * Status: Must be one of the valid enum values (COMPLETED, CANCELLED, etc.)
            // ? Rule::in() checks against allowed values from LessonStatus enum
            'status' => ['required', Rule::in(LessonStatus::values())],

            // * Topic: Required ONLY if lesson is COMPLETED, otherwise optional
            // ? Conditional validation: "What was taught?" matters only for completed lessons
            'topic' => 'required_if:status,'.LessonStatus::COMPLETED->value.'|nullable|string',

            // * Homework: Always optional
            'homework' => 'nullable|string',

            // * Comments: Required ONLY if lesson is TEACHER_CANCELLED
            // ? Forces teacher to explain why they cancelled
            'comments' => 'required_if:status,'.LessonStatus::TEACHER_CANCELLED->value.'|nullable|string',
        ];
    }

    /**
     * * Custom Error Messages: User-friendly validation feedback
     */
    public function messages(): array
    {
        return [
            // ? Explain WHY validation failed in human terms
            'topic.required_if' => 'Topic is required for completed lessons.',
            'comments.required_if' => 'Reason is required when cancelling a lesson.',
        ];
    }
}
