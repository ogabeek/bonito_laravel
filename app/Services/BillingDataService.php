<?php

namespace App\Services;

use App\Models\Teacher;
use App\Repositories\LessonRepository;
use Illuminate\Http\Request;

/**
 * * SERVICE: Aggregates all data for billing/statistics views
 * * Orchestrator pattern - coordinates multiple services to build view data
 */
class BillingDataService
{
    public function __construct(
        protected CalendarService $calendar,
        protected LessonStatisticsService $statsService,
        protected StudentBalanceService $studentBalanceService,
        protected TeacherStatsService $teacherStatsService,
        protected LessonRepository $lessonRepo
    ) {}

    /**
     * * Main entry point - builds all data for admin billing view
     * ? $billing mode: true = 26th-25th period, false = calendar month
     */
    public function build(Request $request): array
    {
        $billing = $request->boolean('billing');

        $calendarData = $this->calendar->getMonthData($request);
        $currentMonth = $calendarData['currentMonth'];
        $prevMonth = $calendarData['prevMonth'];
        $nextMonth = $calendarData['nextMonth'];

        $periodLessons = $this->lessonRepo->getForPeriod($currentMonth, $billing, ['teacher', 'student']);

        $teachers = Teacher::withFullDetails()->get();
        $students = $this->studentBalanceService->enrichStudentsWithBalance();

        $periodStats = $this->statsService->calculateStats($periodLessons);
        $studentStats = $this->statsService->calculateStatsByStudent($periodLessons);
        $teacherStats = $this->statsService->calculateStatsByTeacher($periodLessons);

        $yearLessons = $this->lessonRepo->getForYear($currentMonth->year, ['teacher', 'student']);
        $yearStatsByMonth = $this->statsService->calculateStatsByMonth($yearLessons);

        $studentMonthStats = $this->buildMonthlyStatsByGroup($yearLessons, 'student_id');
        $teacherMonthStats = $this->buildMonthlyStatsByGroup($yearLessons, 'teacher_id');
        $months = range(1, 12);

        $teacherStudentCounts = $this->teacherStatsService->buildTeacherStudentStats($teachers, $periodLessons);

        // * compact() creates array from variable names - Laravel convention
        return compact(
            'teachers',
            'students',
            'currentMonth',
            'prevMonth',
            'nextMonth',
            'periodStats',
            'studentStats',
            'teacherStats',
            'billing',
            'yearStatsByMonth',
            'studentMonthStats',
            'teacherMonthStats',
            'months',
            'teacherStudentCounts'
        );
    }

    /**
     * * Groups lessons by student/teacher, then by month, calculates stats for each
     */
    protected function buildMonthlyStatsByGroup($lessons, string $groupKey)
    {
        return $lessons->groupBy($groupKey)->map(function ($lessonsForGroup) {
            return $lessonsForGroup
                ->groupBy(fn ($lesson) => (int) $lesson->class_date->format('n'))
                ->map(fn ($monthLessons) => $this->statsService->calculateStats($monthLessons));
        });
    }
}
