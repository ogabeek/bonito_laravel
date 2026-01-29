<?php

namespace App\Enums;

use App\Enums\Traits\HasValues;

enum StudentStatus: string
{
    use HasValues;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case HOLIDAY = 'holiday';
    case FINISHED = 'finished';
    case DROPPED = 'dropped';

    /**
     * Get a human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::HOLIDAY => 'On Holiday',
            self::FINISHED => 'Finished',
            self::DROPPED => 'Dropped',
        };
    }

    /**
     * Get the default status for new students
     */
    public static function default(): self
    {
        return self::ACTIVE;
    }

    /**
     * Get CSS class for badge styling
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-green-100 text-green-800',
            self::INACTIVE => 'bg-gray-100 text-gray-800',
            self::HOLIDAY => 'bg-blue-100 text-blue-800',
            self::FINISHED => 'bg-purple-100 text-purple-800',
            self::DROPPED => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Get CSS variable for dot color (dedicated student status colors)
     */
    public function dotColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'var(--color-student-active)',
            self::INACTIVE => 'var(--color-student-inactive)',
            self::HOLIDAY => 'var(--color-student-holiday)',
            self::FINISHED => 'var(--color-student-finished)',
            self::DROPPED => 'var(--color-student-dropped)',
        };
    }

    /**
     * Check if student is currently enrolled (active or on holiday)
     */
    public function isEnrolled(): bool
    {
        return in_array($this, [self::ACTIVE, self::HOLIDAY]);
    }
}
