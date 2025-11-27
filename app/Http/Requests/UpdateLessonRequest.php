<?php

namespace App\Http\Requests;

class UpdateLessonRequest extends LessonRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $lesson = $this->route('lesson');
        return session('teacher_id') == $lesson->teacher_id;
    }

}
