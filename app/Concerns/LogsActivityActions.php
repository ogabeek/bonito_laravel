<?php

namespace App\Concerns;

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
        mixed $subject,
        string $action,
        array $properties = [],
        mixed $causer = null,
        ?string $logName = null,
    ): void {
        // Auto-add name for quick log identification
        if (isset($subject->name)) {
            $properties['name'] = $subject->name;
        }

        // * Uses already-loaded relationship to avoid N+1
        if (method_exists($subject, 'student') && $subject->relationLoaded('student') && $subject->student) {
            $properties['student_name'] = $subject->student->name;
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
