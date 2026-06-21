<?php

use App\Enums\StudentStatus;

it('uses the configured student status palette', function () {
    expect(StudentStatus::ACTIVE->badgeClass())->toContain('green')
        ->and(StudentStatus::HOLIDAY->badgeClass())->toContain('violet')
        ->and(StudentStatus::FINISHED->badgeClass())->toContain('slate')
        ->and(StudentStatus::DROPPED->badgeClass())->toContain('red');
});
