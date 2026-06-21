<?php

use App\Enums\LessonStatus;

it('uses the shared lesson chip palette and labels', function () {
    expect(LessonStatus::COMPLETED->badgeClass())->toContain('green')
        ->and(LessonStatus::STUDENT_ABSENT->badgeClass())->toContain('red-50')
        ->and(LessonStatus::STUDENT_CANCELLED->badgeClass())->toContain('gray-100')
        ->and(LessonStatus::TEACHER_CANCELLED->badgeClass())->toContain('orange-50')
        ->and(LessonStatus::STUDENT_ABSENT->displayLabel())->toBe('Absent')
        ->and(LessonStatus::STUDENT_CANCELLED->displayLabel())->toBe('Canceled by student');
});
