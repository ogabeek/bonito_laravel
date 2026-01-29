<?php

namespace App\Repositories;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Repository for querying Lesson data.
 *
 * Centralizes all lesson queries to avoid duplication in controllers/services.
 */
class LessonRepository
{
    /**
     * Create a base query with optional eager loading.
     *
     * @param  array<string>  $with  Relationships to eager load
     */
    protected function baseQuery(array $with = []): Builder
    {
        return Lesson::query()->when(! empty($with), fn ($q) => $q->with($with));
    }

    /**
     * Get lessons for a specific month.
     *
     * @param  array<string>  $with  Relationships to eager load
     */
    public function getForMonth(Carbon $month, array $with = []): Collection
    {
        return $this->baseQuery($with)
            ->forMonth($month)
            ->get();
    }

    /**
     * Get lessons for a specific teacher in a given month.
     *
     * @param  array<string>  $with  Relationships to eager load
     */
    public function getForTeacher(int $teacherId, Carbon $month, array $with = ['student']): Collection
    {
        return $this->baseQuery($with)
            ->where('teacher_id', $teacherId)
            ->forMonth($month)
            ->orderBy('class_date', 'desc')
            ->get();
    }

    /**
     * Get lessons for a specific student.
     *
     * @param  array<string>  $with  Relationships to eager load
     */
    public function getForStudent(int $studentId, array $with = ['teacher']): Collection
    {
        return $this->baseQuery($with)
            ->where('student_id', $studentId)
            ->orderBy('class_date', 'desc')
            ->get();
    }

    /**
     * Get past lessons for a student.
     *
     * @param  array<string>  $with  Relationships to eager load
     */
    public function getPastForStudent(int $studentId, array $with = ['teacher']): Collection
    {
        return $this->baseQuery($with)
            ->where('student_id', $studentId)
            ->past()
            ->orderBy('class_date', 'desc')
            ->get();
    }

    /**
     * Get lessons for a specific period (calendar month or billing cycle).
     *
     * @param  bool  $isBilling  Whether to use billing period (26th to 25th) or calendar month
     * @param  array<string>  $with  Relationships to eager load
     */
    public function getForPeriod(Carbon $month, bool $isBilling = false, array $with = []): Collection
    {
        if (! $isBilling) {
            return $this->getForMonth($month, $with);
        }

        // Billing period: configurable start/end days
        $startDay = config('billing.period_start_day', 26);
        $endDay = config('billing.period_end_day', 25);
        $periodStart = $month->copy()->subMonthNoOverflow()->day($startDay);
        $periodEnd = $month->copy()->day($endDay)->endOfDay();

        return $this->baseQuery($with)
            ->whereBetween('class_date', [$periodStart, $periodEnd])
            ->get();
    }

    /**
     * Get lessons for a specific year.
     *
     * @param  array<string>  $with  Relationships to eager load
     */
    public function getForYear(int $year, array $with = []): Collection
    {
        return $this->baseQuery($with)
            ->whereYear('class_date', $year)
            ->get();
    }

    /**
     * Get count of chargeable classes (completed + student absent) by student.
     *
     * @param  string|null  $upToDate  Date to count up to (defaults to today)
     * @return Collection<int, int> Key-value pairs of student_id => count
     */
    public function getUsedCountsByStudent(?string $upToDate = null): Collection
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
