<?php

namespace App\Services;

use App\Enums\LessonStatus;
use Illuminate\Support\Collection;

class LessonStatisticsService
{
    /**
     * Calculate lesson statistics from a collection
     *
     * @param Collection $lessons
     * @return array
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
     * Calculate statistics grouped by teacher
     *
     * @param Collection $lessons
     * @return Collection
     */
    public function calculateStatsByTeacher(Collection $lessons): Collection
    {
        return $lessons->groupBy('teacher_id')->map(fn($teacherLessons) => $this->calculateStats($teacherLessons));
    }

    /**
     * Calculate statistics grouped by student
     *
     * @param Collection $lessons
     * @return Collection
     */
    public function calculateStatsByStudent(Collection $lessons): Collection
    {
        return $lessons->groupBy('student_id')->map(fn($studentLessons) => $this->calculateStats($studentLessons));
    }

    /**
     * Count lessons by status
     *
     * @param Collection $lessons
     * @param LessonStatus $status
     * @return int
     */
    private function countByStatus(Collection $lessons, LessonStatus $status): int
    {
        return $lessons->filter(fn($lesson) => $lesson->status === $status)->count();
    }

    public function calculateStatsByMonth(Collection $lessons): Collection
    {
        return $lessons->groupBy(fn($lesson) => $lesson->class_date->format('Y-m'))->map(function($monthLessons) {
            return $this->calculateStats($monthLessons);
        });
    }
}
