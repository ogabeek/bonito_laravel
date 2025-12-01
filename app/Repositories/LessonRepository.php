<?php

namespace App\Repositories;

use App\Enums\LessonStatus;
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

    /**
     * Get lessons for a specific month or billing period
     *
     * @param Carbon $month
     * @param bool $isBilling Whether to use billing period (26th to 25th) or calendar month
     * @param array $with Relationships to eager load
     * @return Collection
     */
    public function getForPeriod(Carbon $month, bool $isBilling = false, array $with = []): Collection
    {
        if ($isBilling) {
            // Billing period: 26th of previous month to 25th of current month
            $periodStart = $month->copy()->subMonthNoOverflow()->day(26);
            $periodEnd = $month->copy()->day(25)->endOfDay();

            return Lesson::query()
                ->when(!empty($with), fn($q) => $q->with($with))
                ->whereBetween('class_date', [$periodStart, $periodEnd])
                ->get();
        }

        // Regular calendar month
        return $this->getForMonth($month, $with);
    }

    /**
     * Get lessons for a specific year
     *
     * @param int $year
     * @param array $with Relationships to eager load
     * @return Collection
     */
    public function getForYear(int $year, array $with = []): Collection
    {
        return Lesson::query()
            ->when(!empty($with), fn($q) => $q->with($with))
            ->whereYear('class_date', $year)
            ->get();
    }

    /**
     * Get count of used classes by student (chargeable statuses)
     *
     * @param string|null $upToDate Date to count up to (defaults to today)
     * @return \Illuminate\Support\Collection Key-value pairs of student_id => count
     */
    public function getUsedCountsByStudent(?string $upToDate = null): \Illuminate\Support\Collection
    {
        $chargeableStatuses = [LessonStatus::COMPLETED, LessonStatus::STUDENT_ABSENT];
        $date = $upToDate ?? now()->toDateString();

        return Lesson::whereDate('class_date', '<=', $date)
            ->whereIn('status', $chargeableStatuses)
            ->selectRaw('student_id, count(*) as used')
            ->groupBy('student_id')
            ->pluck('used', 'student_id');
    }
}
