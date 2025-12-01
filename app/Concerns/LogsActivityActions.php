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
        if (isset($subject->student) && isset($subject->student->name)) {
            $properties['student_name'] = $subject->student->name;
        }
        if (isset($properties['student_id']) && empty($properties['student_name'] ?? null) && method_exists($subject, 'student')) {
            $student = $subject->student()->first();
            if ($student && isset($student->name)) {
                $properties['student_name'] = $student->name;
            }
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
