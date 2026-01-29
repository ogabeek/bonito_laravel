<?php

namespace App\Enums\Traits;

/**
 * * TRAIT: Adds values() helper to enums
 * ? Used for validation: Rule::in(LessonStatus::values())
 */
trait HasValues
{
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
