<?php

namespace App\Repositories;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LessonRepository
{
    /**
     * Get lessons for a specific month
     *
     * @param Carbon $month
     * @param array $with Relationships to eager load
     * @return Collection
     */
    public function getForMonth(Carbon $month, array $with = []): Collection
    {
        return Lesson::query()
            ->when(!empty($with), fn($q) => $q->with($with))
            ->forMonth($month)
            ->get();
    }

    /**
     * Get lessons for a specific teacher in a given month
     *
     * @param int $teacherId
     * @param Carbon $month
     * @param array $with Relationships to eager load
     * @return Collection
     */
    public function getForTeacher(int $teacherId, Carbon $month, array $with = ['student']): Collection
    {
        return Lesson::where('teacher_id', $teacherId)
            ->forMonth($month)
            ->with($with)
            ->orderBy('class_date', 'desc')
            ->get();
    }

    /**
     * Get lessons for a specific student
     *
     * @param int $studentId
     * @param array $with Relationships to eager load
     * @return Collection
     */
    public function getForStudent(int $studentId, array $with = ['teacher']): Collection
    {
        return Lesson::where('student_id', $studentId)
            ->with($with)
            ->orderBy('class_date', 'desc')
            ->get();
    }

    /**
     * Get upcoming lessons for a student
     *
     * @param int $studentId
     * @param array $with Relationships to eager load
     * @return Collection
     */
    public function getUpcomingForStudent(int $studentId, array $with = ['teacher']): Collection
    {
        return Lesson::where('student_id', $studentId)
            ->upcoming()
            ->with($with)
            ->orderBy('class_date', 'asc')
            ->get();
    }

    /**
     * Get past lessons for a student
     *
     * @param int $studentId
     * @param array $with Relationships to eager load
     * @return Collection
     */
    public function getPastForStudent(int $studentId, array $with = ['teacher']): Collection
    {
        return Lesson::where('student_id', $studentId)
            ->past()
            ->with($with)
            ->orderBy('class_date', 'desc')
            ->get();
    }
}
