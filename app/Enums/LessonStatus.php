<?php

namespace App\Enums;

enum LessonStatus: string
{
    case COMPLETED = 'completed';
    case STUDENT_ABSENT = 'student_absent';
    case TEACHER_CANCELLED = 'teacher_cancelled';

    /**
     * Get a human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::COMPLETED => 'Completed',
            self::STUDENT_ABSENT => 'Student Absent',
            self::TEACHER_CANCELLED => 'Teacher Cancelled',
        };
    }

    /**
     * Get all status values as array (for validation)
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get CSS class for badge styling
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::COMPLETED => 'bg-green-100 text-green-800',
            self::STUDENT_ABSENT => 'bg-yellow-100 text-yellow-800',
            self::TEACHER_CANCELLED => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Get CSS class for lesson card styling
     */
    public function cssClass(): string
    {
        return match($this) {
            self::COMPLETED => 'completed',
            self::STUDENT_ABSENT => 'absent',
            self::TEACHER_CANCELLED => 'cancelled',
        };
    }
}
