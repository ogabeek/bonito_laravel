<?php

use App\Enums\LessonStatus;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Repositories\LessonRepository;
use Carbon\Carbon;

beforeEach(function () {
    $this->repo = new LessonRepository;
    $this->teacher = Teacher::factory()->create();
    $this->student = Student::factory()->create();
});

/** Helper: create a lesson for the default student/teacher on a given date. */
function lessonOn(string $date, LessonStatus $status = LessonStatus::COMPLETED, ?Student $student = null): Lesson
{
    return Lesson::factory()->create([
        'teacher_id' => test()->teacher->id,
        'student_id' => ($student ?? test()->student)->id,
        'class_date' => $date,
        'status' => $status,
    ]);
}

describe('getForPeriod (billing window)', function () {
    it('includes only lessons within the billing window (period start -> end)', function () {
        config(['billing.period_start_day' => 24, 'billing.period_end_day' => 23]);

        // Billing period for Feb 2026 = Jan 24 -> Feb 23.
        lessonOn('2026-01-23'); // day before window -> excluded
        lessonOn('2026-01-24'); // window start -> included
        lessonOn('2026-02-10'); // mid window -> included
        lessonOn('2026-02-23'); // window end -> included
        lessonOn('2026-02-24'); // day after window -> excluded

        $lessons = $this->repo->getForPeriod(Carbon::create(2026, 2, 15), isBilling: true);

        expect($lessons->pluck('class_date')->map->toDateString()->sort()->values()->all())
            ->toBe(['2026-01-24', '2026-02-10', '2026-02-23']);
    });

    it('falls back to the calendar month when billing is off', function () {
        lessonOn('2026-01-26'); // prev month -> excluded in calendar mode
        lessonOn('2026-02-10'); // included
        lessonOn('2026-02-26'); // still February -> included

        $lessons = $this->repo->getForPeriod(Carbon::create(2026, 2, 15), isBilling: false);

        expect($lessons->pluck('class_date')->map->toDateString()->sort()->values()->all())
            ->toBe(['2026-02-10', '2026-02-26']);
    });
});

describe('getUsedCountsByStudent (chargeable rule)', function () {
    it('counts completed and student_absent but not cancellations', function () {
        lessonOn('2026-06-01', LessonStatus::COMPLETED);
        lessonOn('2026-06-02', LessonStatus::COMPLETED);
        lessonOn('2026-06-10', LessonStatus::STUDENT_ABSENT);
        lessonOn('2026-06-05', LessonStatus::STUDENT_CANCELLED);  // not chargeable
        lessonOn('2026-06-05', LessonStatus::TEACHER_CANCELLED);  // not chargeable

        $counts = $this->repo->getUsedCountsByStudent('2026-06-20');

        expect($counts[$this->student->id])->toBe(3);
    });

    it('excludes lessons after the cutoff date', function () {
        lessonOn('2026-06-01', LessonStatus::COMPLETED);
        lessonOn('2026-06-30', LessonStatus::COMPLETED); // after cutoff

        $counts = $this->repo->getUsedCountsByStudent('2026-06-20');

        expect($counts[$this->student->id])->toBe(1);
    });

    it('groups counts per student and omits students with no chargeable lessons', function () {
        $other = Student::factory()->create();

        lessonOn('2026-06-01', LessonStatus::COMPLETED);
        lessonOn('2026-06-02', LessonStatus::STUDENT_ABSENT);
        lessonOn('2026-06-03', LessonStatus::TEACHER_CANCELLED, $other); // only non-chargeable

        $counts = $this->repo->getUsedCountsByStudent('2026-06-20');

        expect($counts[$this->student->id])->toBe(2)
            ->and($counts->has($other->id))->toBeFalse();
    });
});
