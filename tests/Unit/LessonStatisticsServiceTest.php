<?php

use App\Enums\LessonStatus;
use App\Services\LessonStatisticsService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new LessonStatisticsService;
});

it('calculates stats for empty collection', function () {
    $stats = $this->service->calculateStats(collect([]));

    expect($stats)->toBe([
        'total' => 0,
        'completed' => 0,
        'student_absent' => 0,
        'student_cancelled' => 0,
        'teacher_cancelled' => 0,
    ]);
});

it('calculates stats correctly for mixed statuses', function () {
    $lessons = collect([
        (object) ['status' => LessonStatus::COMPLETED],
        (object) ['status' => LessonStatus::COMPLETED],
        (object) ['status' => LessonStatus::COMPLETED],
        (object) ['status' => LessonStatus::STUDENT_ABSENT],
        (object) ['status' => LessonStatus::STUDENT_CANCELLED],
        (object) ['status' => LessonStatus::TEACHER_CANCELLED],
        (object) ['status' => LessonStatus::TEACHER_CANCELLED],
    ]);

    $stats = $this->service->calculateStats($lessons);

    expect($stats['total'])->toBe(7)
        ->and($stats['completed'])->toBe(3)
        ->and($stats['student_absent'])->toBe(1)
        ->and($stats['student_cancelled'])->toBe(1)
        ->and($stats['teacher_cancelled'])->toBe(2);
});

it('groups stats by teacher', function () {
    $lessons = collect([
        (object) ['teacher_id' => 1, 'status' => LessonStatus::COMPLETED],
        (object) ['teacher_id' => 1, 'status' => LessonStatus::COMPLETED],
        (object) ['teacher_id' => 2, 'status' => LessonStatus::STUDENT_ABSENT],
        (object) ['teacher_id' => 2, 'status' => LessonStatus::COMPLETED],
    ]);

    $statsByTeacher = $this->service->calculateStatsByTeacher($lessons);

    expect($statsByTeacher)->toHaveCount(2)
        ->and($statsByTeacher[1]['total'])->toBe(2)
        ->and($statsByTeacher[1]['completed'])->toBe(2)
        ->and($statsByTeacher[2]['total'])->toBe(2)
        ->and($statsByTeacher[2]['student_absent'])->toBe(1);
});

it('groups stats by student', function () {
    $lessons = collect([
        (object) ['student_id' => 10, 'status' => LessonStatus::COMPLETED],
        (object) ['student_id' => 10, 'status' => LessonStatus::STUDENT_CANCELLED],
        (object) ['student_id' => 20, 'status' => LessonStatus::COMPLETED],
    ]);

    $statsByStudent = $this->service->calculateStatsByStudent($lessons);

    expect($statsByStudent)->toHaveCount(2)
        ->and($statsByStudent[10]['total'])->toBe(2)
        ->and($statsByStudent[10]['student_cancelled'])->toBe(1)
        ->and($statsByStudent[20]['total'])->toBe(1);
});

it('groups stats by month', function () {
    $lessons = collect([
        (object) ['class_date' => Carbon::create(2025, 1, 15), 'status' => LessonStatus::COMPLETED],
        (object) ['class_date' => Carbon::create(2025, 1, 20), 'status' => LessonStatus::COMPLETED],
        (object) ['class_date' => Carbon::create(2025, 2, 5), 'status' => LessonStatus::STUDENT_ABSENT],
        (object) ['class_date' => Carbon::create(2025, 3, 10), 'status' => LessonStatus::COMPLETED],
    ]);

    $statsByMonth = $this->service->calculateStatsByMonth($lessons);

    expect($statsByMonth)->toHaveCount(3)
        ->and($statsByMonth['2025-01']['total'])->toBe(2)
        ->and($statsByMonth['2025-01']['completed'])->toBe(2)
        ->and($statsByMonth['2025-02']['total'])->toBe(1)
        ->and($statsByMonth['2025-02']['student_absent'])->toBe(1)
        ->and($statsByMonth['2025-03']['total'])->toBe(1);
});

it('calculates weekly distribution for a selected calendar year', function () {
    $lessons = collect([
        (object) ['class_date' => Carbon::create(2026, 1, 1), 'status' => LessonStatus::COMPLETED],
        (object) ['class_date' => Carbon::create(2026, 1, 7), 'status' => LessonStatus::STUDENT_ABSENT],
        (object) ['class_date' => Carbon::create(2026, 2, 1), 'status' => LessonStatus::COMPLETED],
        (object) ['class_date' => Carbon::create(2025, 1, 1), 'status' => LessonStatus::COMPLETED],
    ]);

    $distribution = $this->service->calculateWeeklyDistribution($lessons, 2026);

    expect($distribution['year'])->toBe(2026)
        ->and($distribution['weeks'][0]['count'])->toBe(2)
        ->and($distribution['weeks'][0]['completed'])->toBe(1)
        ->and($distribution['weeks'][0]['other'])->toBe(1)
        ->and($distribution['total'])->toBe(3)
        ->and($distribution['max'])->toBe(2);
});

it('keeps weekly buckets within a single calendar month', function () {
    // A plain 7-day bucket would run Jan 29 – Feb 4; the Feb class must stay in
    // February, not leak into January's weeks (which drive the chart's per-month
    // green cells and watermark total).
    $lessons = collect([
        (object) ['class_date' => Carbon::create(2026, 1, 29), 'status' => LessonStatus::COMPLETED],
        (object) ['class_date' => Carbon::create(2026, 2, 2), 'status' => LessonStatus::COMPLETED],
    ]);

    $weeks = collect($this->service->calculateWeeklyDistribution($lessons, 2026)['weeks']);
    $completedByMonth = $weeks->groupBy(fn ($week) => $week['start']->format('Y-m'))
        ->map(fn ($monthWeeks) => $monthWeeks->sum('completed'));

    expect($weeks->every(fn ($week) => $week['start']->format('Y-m') === $week['end']->format('Y-m')))->toBeTrue()
        ->and($completedByMonth['2026-01'])->toBe(1)
        ->and($completedByMonth['2026-02'])->toBe(1);
});
