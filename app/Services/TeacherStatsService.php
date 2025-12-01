<?php

namespace App\Services;

use App\Models\Teacher;
use Illuminate\Support\Collection;

class TeacherStatsService
{
    public function __construct(
        protected LessonStatisticsService $statsService
    ) {}

    /**
     * Build teacher-student stats breakdown for a period
     *
     * @param Collection $teachers Collection of teachers
     * @param Collection $periodLessons Lessons for the period
     * @return Collection Teacher ID => array of student stats
     */
    public function buildTeacherStudentStats(Collection $teachers, Collection $periodLessons): Collection
    {
        return $teachers->mapWithKeys(function($teacher) use ($periodLessons) {
            $lessonsForTeacher = $periodLessons->where('teacher_id', $teacher->id);
            $byStudent = $lessonsForTeacher
                ->groupBy('student_id')
                ->map(function($studentLessons) {
                    $student = $studentLessons->first()->student;
                    return [
                        'name' => $student?->name ?? 'Unknown',
                        'stats' => $this->statsService->calculateStats($studentLessons),
                    ];
                })
                ->sortBy('name')
                ->values();

            return [$teacher->id => $byStudent];
        });
    }
}
