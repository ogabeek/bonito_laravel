<?php

namespace App\Enums;

use App\Enums\Traits\HasValues;

/**
 * Lifecycle of a feedback thread: open while it needs attention,
 * resolved once the admin has handled it.
 */
enum FeedbackStatus: string
{
    use HasValues;

    case OPEN = 'open';
    case RESOLVED = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::RESOLVED => 'Resolved',
        };
    }
}
