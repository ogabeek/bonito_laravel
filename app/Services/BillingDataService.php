<?php

namespace App\Services;

use App\Models\Teacher;
use App\Repositories\LessonRepository;
use Illuminate\Http\Request;

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
     * Build all data needed for billing/stats views.
     *
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $billing = $request->boolean('billing');

        $calendarData = $this->calendar->getMonthData($request);
        $currentMonth = $calendarData['currentMonth'];
        $prevMonth = $calendarData['prevMonth'];
        $nextMonth = $calendarData['nextMonth'];

        // Lessons for period (calendar or billing 26-25)
        $periodLessons = $this->lessonRepo->getForPeriod($currentMonth, $billing, ['teacher', 'student']);

        // Load teachers and students once for all operations
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
     * Build monthly stats grouped by a foreign key (student_id or teacher_id).
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
