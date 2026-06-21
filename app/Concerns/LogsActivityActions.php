<?php

namespace App\Concerns;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Model;

/**
 * * TRAIT: Activity logging helper for controllers
 * * Uses spatie/laravel-activitylog package
 */
trait LogsActivityActions
{
    /**
     * * Log an action with optional causer and properties
     * ? Adds name/student_name automatically if available
     */
    protected function logActivity(
        Model $subject,
        string $action,
        array $properties = [],
        ?Model $causer = null,
        ?string $logName = null,
    ): void {
        // Auto-add name for quick log identification
        $name = $subject->getAttribute('name');
        if ($name !== null) {
            $properties['name'] = $name;
        }

        // * Lessons carry a student; include its name (already-loaded) for log matching
        if ($subject instanceof Lesson && $subject->relationLoaded('student') && $subject->student) {
            $properties['student_name'] = $subject->student->getAttribute('name');
        }

        if ($subject instanceof Lesson && $subject->status === LessonStatus::STUDENT_ABSENT) {
            $properties = array_merge($properties, $subject->absenceFollowUp());
        }

        $logger = activity($logName);

        if ($causer) {
            $logger->causedBy($causer);
        }

        $logger
            ->performedOn($subject)
            ->withProperties(array_merge(['action' => $action], $properties))
            ->log($action);
    }
}
