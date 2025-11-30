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
            'completed' => $lessons->filter(fn($lesson) => $lesson->status === LessonStatus::COMPLETED)->count(),
            'student_absent' => $lessons->filter(fn($lesson) => $lesson->status === LessonStatus::STUDENT_ABSENT)->count(),
            'teacher_cancelled' => $lessons->filter(fn($lesson) => $lesson->status === LessonStatus::TEACHER_CANCELLED)->count(),
        ];
    }

    /**
     * Calculate statistics grouped by student
     *
     * @param Collection $lessons
     * @return Collection
     */
    public function calculateStatsByStudent(Collection $lessons): Collection
    {
        return $lessons->groupBy('student_id')->map(function($studentLessons) {
            return [
                'total' => $studentLessons->count(),
                'completed' => $studentLessons->filter(fn($lesson) => $lesson->status === LessonStatus::COMPLETED)->count(),
            ];
        });
    }
}
