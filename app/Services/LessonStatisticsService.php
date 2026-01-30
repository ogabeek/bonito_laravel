<?php

namespace App\Services;

use App\Enums\LessonStatus;
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

    private function countByStatus(Collection $lessons, LessonStatus $status): int
    {
        return $lessons->filter(fn ($lesson) => $lesson->status === $status)->count();
    }
}
