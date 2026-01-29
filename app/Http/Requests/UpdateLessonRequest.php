<?php

namespace App\Http\Requests;

/**
 * * REQUEST: Update Lesson validation
 * ! Security: Only the lesson's teacher can update it
 */
class UpdateLessonRequest extends LessonRequest
{
    public function authorize(): bool
    {
        $lesson = $this->route('lesson');

        return session('teacher_id') == $lesson->teacher_id;
    }
}
