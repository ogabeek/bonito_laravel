<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Teacher;
use App\Repositories\LessonRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * * SERVICE: Lean data loader for admin dashboard
 * * Unlike BillingDataService, this does NOT call Google Sheets or load full year
 */
class DashboardDataService
{
    public function __construct(
        protected LessonRepository $lessonRepo,
        protected LessonStatisticsService $statsService
    ) {}

    /**
     * * Load students with teachers (no balance data)
     */
    public function getStudents(): Collection
    {
        return Student::withFullDetails()
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                $student->teacher_ids = $student->teachers->pluck('id')->toArray();

                return $student;
            });
    }

    /**
     * * Load active teachers with counts
     */
    public function getTeachers(): Collection
    {
        return Teacher::withFullDetails()->get();
    }

    /**
     * * Load archived teachers for restore functionality
     */
    public function getArchivedTeachers(): Collection
    {
        return Teacher::onlyTrashed()->get();
    }

    /**
     * * Get lessons for month, grouped by student_date for calendar lookup
     */
    public function getLessonsForMonth(Carbon $month): Collection
    {
        return $this->lessonRepo
            ->getForMonth($month, ['teacher', 'student'])
            ->groupBy(fn ($lesson) => $lesson->student_id.'_'.$lesson->class_date->format('Y-m-d'));
    }

    /**
     * * Get raw lessons collection for stats calculation
     */
    public function getLessonsCollection(Carbon $month, bool $billing = false): Collection
    {
        return $this->lessonRepo->getForPeriod($month, $billing, ['teacher', 'student']);
    }

    /**
     * * Calculate period stats from lessons
     */
    public function calculateStats(Collection $lessons): array
    {
        return $this->statsService->calculateStats($lessons);
    }

    /**
     * * Calculate stats grouped by student
     */
    public function calculateStatsByStudent(Collection $lessons): Collection
    {
        return $this->statsService->calculateStatsByStudent($lessons);
    }

    /**
     * * Calculate stats grouped by teacher
     */
    public function calculateStatsByTeacher(Collection $lessons): Collection
    {
        return $this->statsService->calculateStatsByTeacher($lessons);
    }
}
