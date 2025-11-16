<?php

namespace App\Services;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LessonService
{
    /**
     * Get lessons for a teacher in a specific month
     */
    public function getLessonsForMonth(int $teacherId, Carbon $date): Collection
    {
        return Lesson::where('teacher_id', $teacherId)
            ->whereYear('class_date', $date->year)
            ->whereMonth('class_date', $date->month)
            ->with('student')
            ->orderBy('class_date', 'asc')
            ->get();
    }

    /**
     * Group lessons by week
     */
    public function groupLessonsByWeek(Collection $lessons): Collection
    {
        return $lessons->groupBy(function($lesson) {
            return $lesson->class_date->startOfWeek()->format('Y-m-d');
        });
    }

    /**
     * Calculate monthly statistics
     */
    public function calculateStats(Collection $lessons): array
    {
        return [
            'total' => $lessons->count(),
            'completed' => $lessons->where('status', 'completed')->count(),
            'student_absent' => $lessons->where('status', 'student_absent')->count(),
            'teacher_cancelled' => $lessons->where('status', 'teacher_cancelled')->count(),
        ];
    }

    /**
     * Create a new lesson
     */
    public function createLesson(array $data): Lesson
    {
        return Lesson::create([
            'teacher_id' => $data['teacher_id'],
            'student_id' => $data['student_id'],
            'class_date' => $data['class_date'],
            'status' => $data['status'],
            'topic' => $data['topic'] ?? '',
            'homework' => $data['homework'] ?? null,
            'comments' => $data['comments'] ?? null,
        ]);
    }

    /**
     * Update an existing lesson
     */
    public function updateLesson(Lesson $lesson, array $data): Lesson
    {
        $lesson->update([
            'status' => $data['status'],
            'topic' => $data['topic'] ?? '',
            'homework' => $data['homework'] ?? null,
            'comments' => $data['comments'] ?? null,
        ]);

        return $lesson->fresh();
    }

    /**
     * Check if teacher owns the lesson
     */
    public function teacherOwnsLesson(int $teacherId, Lesson $lesson): bool
    {
        return $lesson->teacher_id === $teacherId;
    }
}
