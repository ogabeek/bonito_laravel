<?php

namespace App\Services;

use App\Enums\LessonStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * * SERVICE: Calculates lesson statistics from collections
 * * Pure functions - no side effects, just transforms data
 */
class LessonStatisticsService
{
    /**
     * * Core stats calculation - counts lessons by status
     */
    public function calculateStats(Collection $lessons): array
    {
        return [
            'total' => $lessons->count(),
            'completed' => $this->countByStatus($lessons, LessonStatus::COMPLETED),
            'student_absent' => $this->countByStatus($lessons, LessonStatus::STUDENT_ABSENT),
            'student_cancelled' => $this->countByStatus($lessons, LessonStatus::STUDENT_CANCELLED),
            'teacher_cancelled' => $this->countByStatus($lessons, LessonStatus::TEACHER_CANCELLED),
        ];
    }

    /**
     * * Returns [teacher_id => stats_array]
     */
    public function calculateStatsByTeacher(Collection $lessons): Collection
    {
        return $lessons->groupBy('teacher_id')->map(fn ($teacherLessons) => $this->calculateStats($teacherLessons));
    }

    /**
     * * Returns [student_id => stats_array]
     */
    public function calculateStatsByStudent(Collection $lessons): Collection
    {
        return $lessons->groupBy('student_id')->map(fn ($studentLessons) => $this->calculateStats($studentLessons));
    }

    /**
     * * Returns ['2024-01' => stats_array, '2024-02' => stats_array, ...]
     */
    public function calculateStatsByMonth(Collection $lessons): Collection
    {
        return $lessons->groupBy(fn ($lesson) => $lesson->class_date->format('Y-m'))->map(function ($monthLessons) {
            return $this->calculateStats($monthLessons);
        });
    }

    public function calculateWeeklyDistribution(Collection $lessons, int $year): array
    {
        $distribution = $this->calculateWeeklyDistributionForRange(
            $lessons,
            Carbon::create($year, 1, 1),
            Carbon::create($year, 12, 31)
        );
        $distribution['year'] = $year;

        return $distribution;
    }

    /**
     * @return array{
     *     start: \Carbon\Carbon,
     *     end: \Carbon\Carbon,
     *     total: int,
     *     max: int,
     *     weeks: \Illuminate\Support\Collection<int, array{
     *         week: int,
     *         count: int,
     *         completed: int,
     *         other: int,
     *         start: \Carbon\Carbon,
     *         end: \Carbon\Carbon
     *     }>
     * }
     */
    public function calculateWeeklyDistributionForRange(Collection $lessons, Carbon $start, Carbon $end): array
    {
        $rangeStart = $start->copy()->startOfDay();
        $rangeEnd = $end->copy()->endOfDay();

        $lessonsInRange = $lessons
            ->filter(fn ($lesson) => $lesson->class_date->betweenIncluded($rangeStart, $rangeEnd));

        // Weeks are 7-day buckets, but a week never crosses a month boundary:
        // it is clipped at month-end so every week (and the month watermark
        // summed from its weeks in the chart) belongs to exactly one calendar
        // month. Without this, the Jan 29–Feb 4 bucket credits early-February
        // classes to January.
        $weeks = collect();
        $weekNumber = 0;
        $cursor = $rangeStart->copy();

        while ($cursor->lessThanOrEqualTo($rangeEnd)) {
            $weekStart = $cursor->copy();
            $weekEnd = $weekStart->copy()->addDays(6);

            foreach ([$weekStart->copy()->endOfMonth(), $rangeEnd] as $boundary) {
                if ($weekEnd->greaterThan($boundary)) {
                    $weekEnd = $boundary->copy();
                }
            }

            $weekLessons = $lessonsInRange->filter(
                fn ($lesson) => $lesson->class_date->betweenIncluded($weekStart, $weekEnd->copy()->endOfDay())
            );
            $completed = $this->countByStatus($weekLessons, LessonStatus::COMPLETED);
            $count = $weekLessons->count();

            $weeks->push([
                'week' => ++$weekNumber,
                'count' => $count,
                'completed' => $completed,
                'other' => $count - $completed,
                'start' => $weekStart,
                'end' => $weekEnd,
            ]);

            $cursor = $weekEnd->copy()->addDay()->startOfDay();
        }

        return [
            'start' => $rangeStart,
            'end' => $rangeEnd,
            'total' => $weeks->sum('count'),
            'max' => max(1, (int) $weeks->max('count')),
            'weeks' => $weeks->values(),
        ];
    }

    private function countByStatus(Collection $lessons, LessonStatus $status): int
    {
        return $lessons->filter(fn ($lesson) => $lesson->status === $status)->count();
    }
}
