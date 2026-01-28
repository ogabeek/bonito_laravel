<?php

use App\Enums\LessonStatus;
use App\Services\LessonStatisticsService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new LessonStatisticsService();
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
