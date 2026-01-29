<?php

namespace App\Services;

use App\Enums\LessonStatus;
use Illuminate\Support\Collection;

/**
 * Calculates lesson statistics from collections of lessons.
 */
class LessonStatisticsService
{
    /**
     * Calculate lesson statistics from a collection.
     *
     * @return array{total: int, completed: int, student_absent: int, student_cancelled: int, teacher_cancelled: int}
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
     * Calculate statistics grouped by teacher.
     *
     * @return Collection<int, array{total: int, completed: int, student_absent: int, student_cancelled: int, teacher_cancelled: int}>
     */
    public function calculateStatsByTeacher(Collection $lessons): Collection
    {
        return $lessons->groupBy('teacher_id')->map(fn ($teacherLessons) => $this->calculateStats($teacherLessons));
    }

    /**
     * Calculate statistics grouped by student.
     *
     * @return Collection<int, array{total: int, completed: int, student_absent: int, student_cancelled: int, teacher_cancelled: int}>
     */
    public function calculateStatsByStudent(Collection $lessons): Collection
    {
        return $lessons->groupBy('student_id')->map(fn ($studentLessons) => $this->calculateStats($studentLessons));
    }

    /**
     * Calculate statistics grouped by month.
     *
     * @return Collection<string, array{total: int, completed: int, student_absent: int, student_cancelled: int, teacher_cancelled: int}>
     */
    public function calculateStatsByMonth(Collection $lessons): Collection
    {
        return $lessons->groupBy(fn ($lesson) => $lesson->class_date->format('Y-m'))->map(function ($monthLessons) {
            return $this->calculateStats($monthLessons);
        });
    }

    /**
     * Count lessons by status.
     */
    private function countByStatus(Collection $lessons, LessonStatus $status): int
    {
        return $lessons->filter(fn ($lesson) => $lesson->status === $status)->count();
    }
}
