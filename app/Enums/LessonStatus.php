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
            self::COMPLETED => 'border-green-200 bg-green-100 text-green-800',
            self::STUDENT_ABSENT => 'border-red-200 bg-red-50 text-red-700',
            self::STUDENT_CANCELLED => 'border-gray-200 bg-gray-100 text-gray-600',
            self::TEACHER_CANCELLED => 'border-orange-200 bg-orange-50 text-orange-700',
        };
    }

    public function displayLabel(): string
    {
        return match ($this) {
            self::COMPLETED => 'Done',
            self::STUDENT_ABSENT => 'Absent',
            self::STUDENT_CANCELLED => 'Canceled by student',
            self::TEACHER_CANCELLED => 'Canceled by teacher',
        };
    }

    public function textClass(): string
    {
        return match ($this) {
            self::COMPLETED => 'text-green-700',
            self::STUDENT_ABSENT => 'text-red-700',
            self::STUDENT_CANCELLED => 'text-gray-600',
            self::TEACHER_CANCELLED => 'text-orange-700',
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
