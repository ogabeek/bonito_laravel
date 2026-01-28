<?php

namespace App\Enums\Traits;

trait HasValues
{
    /**
     * Get all enum values as array (for validation).
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
