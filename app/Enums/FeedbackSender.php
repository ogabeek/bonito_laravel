<?php

namespace App\Enums;

use App\Enums\Traits\HasValues;

/**
 * Who authored a feedback message: the teacher who opened the report,
 * or the admin replying to it.
 */
enum FeedbackSender: string
{
    use HasValues;

    case TEACHER = 'teacher';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::TEACHER => 'Teacher',
            self::ADMIN => 'Admin',
        };
    }
}
