<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * * SERVICE: Builds per-teacher student breakdown stats
 * * Shows which students each teacher taught and their stats
 */
class TeacherStatsService
{
    public function __construct(
        protected LessonStatisticsService $statsService
    ) {}

    /**
     * * Returns [teacher_id => [{name: 'Student', stats: {...}}, ...]]
     * * Used for drill-down view in admin dashboard
     */
    public function buildTeacherStudentStats(Collection $teachers, Collection $periodLessons): Collection
    {
        return $teachers->mapWithKeys(function ($teacher) use ($periodLessons) {
            $lessonsForTeacher = $periodLessons->where('teacher_id', $teacher->id);
            $byStudent = $lessonsForTeacher
                ->groupBy('student_id')
                ->map(function ($studentLessons) {
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
