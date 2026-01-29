<?php

namespace App\Http\Requests;

/**
 * ! FORM REQUEST: Update Lesson
 * * Purpose: Validates data when teacher updates an existing lesson
 * * Why: Security - ensures teachers can only edit their own lessons
 * * What: Uses parent LessonRequest rules (status, topic, homework, comments)
 */
class UpdateLessonRequest extends LessonRequest
{
    /**
     * * Authorization: Only the lesson's owner (teacher) can update it
     */
    public function authorize(): bool
    {
        // * Get the lesson being updated from route parameter
        // ? route('lesson') extracts the {lesson} from URL: /lessons/{lesson}
        $lesson = $this->route('lesson');

        // ! Security: Check if logged-in teacher owns this lesson
        // ? Prevents Teacher A from editing Teacher B's lessons
        return session('teacher_id') == $lesson->teacher_id;
    }

    // * Validation rules inherited from parent LessonRequest
    // ? No additional rules needed - can't change student or date after creation
}
