<?php

namespace App\Concerns;

trait LogsActivityActions
{
    /**
    * Log an action with optional causer and properties.
    */
    protected function logActivity(
        mixed $subject,
        string $action,
        array $properties = [],
        mixed $causer = null,
        ?string $logName = null,
    ): void {
        // Enrich with names when available for quick identification in logs
        if (isset($subject->name)) {
            $properties['name'] = $subject->name;
        }

        // Use already-loaded student relationship to avoid N+1 query
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
