<?php

namespace App\Repositories;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * * REPOSITORY: Centralized lesson queries
 * ? Why repository? Avoids duplicating query logic across controllers/services
 */
class LessonRepository
{
    /**
     * * Base query builder with optional eager loading
     */
    protected function baseQuery(array $with = []): Builder
    {
        return Lesson::query()->when(! empty($with), fn ($q) => $q->with($with));
    }

    /**
     * * Uses Lesson::scopeForMonth() defined in model
     */
    public function getForMonth(Carbon $month, array $with = []): Collection
    {
        return $this->baseQuery($with)
            ->forMonth($month)
            ->get();
    }

    public function getForTeacher(int $teacherId, Carbon $month, array $with = ['student']): Collection
    {
        return $this->baseQuery($with)
            ->where('teacher_id', $teacherId)
            ->forMonth($month)
            ->orderBy('class_date', 'desc')
            ->get();
    }

    public function getForStudent(int $studentId, array $with = ['teacher']): Collection
    {
        return $this->baseQuery($with)
            ->where('student_id', $studentId)
            ->orderBy('class_date', 'desc')
            ->get();
    }

    /**
     * * Uses Lesson::scopePast() - only lessons before today
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
     * * Billing period: 26th prev month → 25th current month
     * ? Why? Admin bills per this cycle, not calendar month
     */
    public function getForPeriod(Carbon $month, bool $isBilling = false, array $with = []): Collection
    {
        if (! $isBilling) {
            return $this->getForMonth($month, $with);
        }

        $startDay = config('billing.period_start_day', 26);
        $endDay = config('billing.period_end_day', 25);
        $periodStart = $month->copy()->subMonthNoOverflow()->day($startDay);
        $periodEnd = $month->copy()->day($endDay)->endOfDay();

        return $this->baseQuery($with)
            ->whereBetween('class_date', [$periodStart, $periodEnd])
            ->get();
    }

    public function getForDateRange(Carbon $start, Carbon $end, array $with = []): Collection
    {
        return $this->baseQuery($with)
            ->whereBetween('class_date', [$start->startOfDay(), $end->endOfDay()])
            ->get();
    }

    public function getForYear(int $year, array $with = []): Collection
    {
        return $this->baseQuery($with)
            ->whereYear('class_date', $year)
            ->get();
    }

    /**
     * * Returns [student_id => count] of chargeable classes
     * ! Chargeable = completed OR student_absent (not cancelled)
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
