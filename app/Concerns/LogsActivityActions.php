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
