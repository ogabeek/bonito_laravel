<?php

namespace App\Enums;

use App\Enums\Traits\HasValues;

/**
 * * ENUM: Lesson status values
 * ! Chargeable statuses: COMPLETED, STUDENT_ABSENT (student still pays)
 */
enum LessonStatus: string
{
    use HasValues;

    case COMPLETED = 'completed';
    case STUDENT_ABSENT = 'student_absent';
    case STUDENT_CANCELLED = 'student_cancelled';
    case TEACHER_CANCELLED = 'teacher_cancelled';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Completed',
            self::STUDENT_ABSENT => 'Student Absent',
            self::STUDENT_CANCELLED => 'Student Cancelled',
            self::TEACHER_CANCELLED => 'Teacher Cancelled',
        };
    }

    /**
     * * Tailwind badge colors for UI
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::COMPLETED => 'bg-green-100 text-green-800',
            self::STUDENT_ABSENT => 'bg-yellow-100 text-yellow-800',
            self::STUDENT_CANCELLED => 'bg-blue-100 text-blue-800',
            self::TEACHER_CANCELLED => 'bg-red-100 text-red-800',
        };
    }

    /**
     * * CSS class for lesson card styling
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::COMPLETED => 'completed',
            self::STUDENT_ABSENT => 'absent',
            self::STUDENT_CANCELLED => 'student-cancelled',
            self::TEACHER_CANCELLED => 'cancelled',
        };
    }
}
