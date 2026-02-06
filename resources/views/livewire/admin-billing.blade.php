<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Repositories\LessonRepository;
use App\Services\BalanceService;
use App\Services\LessonStatisticsService;
use App\Services\TeacherStatsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component
{
    #[Url]
    public ?int $year = null;

    #[Url]
    public ?int $month = null;

    #[Url]
    public bool $billing = false;

    public bool $balancesLoaded = false;
    public array $balances = [];

    public function mount(): void
    {
        $now = now();
        $this->year = $this->year ?? $now->year;
        $this->month = $this->month ?? $now->month;
    }

    public function loadBalances(): void
    {
        $this->balances = app(BalanceService::class)->getBalances();
        $this->balancesLoaded = true;
    }

    public function refreshBalances(): void
    {
        app(BalanceService::class)->refreshCache();
        $this->balances = app(BalanceService::class)->getBalances();
        $this->balancesLoaded = true;
        session()->flash('success', 'Balance data refreshed from Google Sheets');
    }

    #[Computed]
    public function currentMonth(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromDate($this->year, $this->month, 1);
    }

    #[Computed]
    public function prevMonth(): \Carbon\Carbon
    {
        return $this->currentMonth->copy()->subMonth();
    }

    #[Computed]
    public function nextMonth(): \Carbon\Carbon
    {
        return $this->currentMonth->copy()->addMonth();
    }

    #[Computed]
    public function students(): \Illuminate\Support\Collection
    {
        $students = Student::withFullDetails()->orderBy('name')->get();
        $usedCounts = app(LessonRepository::class)->getUsedCountsByStudent();

        return $students->map(function ($student) use ($usedCounts) {
            $paid = $this->balances[$student->uuid] ?? null;
            $used = $usedCounts[$student->id] ?? 0;

            $student->paid_classes = $paid !== null ? (int) $paid : null;
            $student->used_classes = (int) $used;
            $student->class_balance = $paid !== null ? ((int) $paid - (int) $used) : null;

            return $student;
        });
    }

    #[Computed]
    public function teachers(): \Illuminate\Support\Collection
    {
        return Teacher::withFullDetails()->get();
    }

    #[Computed]
    public function periodLessons(): \Illuminate\Support\Collection
    {
        return app(LessonRepository::class)->getForPeriod($this->currentMonth, $this->billing, ['teacher', 'student']);
    }

    #[Computed]
    public function yearLessons(): \Illuminate\Support\Collection
    {
        return app(LessonRepository::class)->getForYear($this->currentMonth->year, ['teacher', 'student']);
    }

    #[Computed]
    public function periodStats(): array
    {
        return app(LessonStatisticsService::class)->calculateStats($this->periodLessons);
    }

    #[Computed]
    public function studentStats(): \Illuminate\Support\Collection
    {
        return app(LessonStatisticsService::class)->calculateStatsByStudent($this->periodLessons);
    }

    #[Computed]
    public function teacherStats(): \Illuminate\Support\Collection
    {
        return app(LessonStatisticsService::class)->calculateStatsByTeacher($this->periodLessons);
    }

    #[Computed]
    public function yearStatsByMonth(): \Illuminate\Support\Collection
    {
        return app(LessonStatisticsService::class)->calculateStatsByMonth($this->yearLessons);
    }

    #[Computed]
    public function studentMonthStats(): \Illuminate\Support\Collection
    {
        return $this->yearLessons->groupBy('student_id')->map(function ($lessons) {
            return $lessons
                ->groupBy(fn ($l) => (int) $l->class_date->format('n'))
                ->map(fn ($m) => app(LessonStatisticsService::class)->calculateStats($m));
        });
    }

    #[Computed]
    public function teacherMonthStats(): \Illuminate\Support\Collection
    {
        return $this->yearLessons->groupBy('teacher_id')->map(function ($lessons) {
            return $lessons
                ->groupBy(fn ($l) => (int) $l->class_date->format('n'))
                ->map(fn ($m) => app(LessonStatisticsService::class)->calculateStats($m));
        });
    }

    #[Computed]
    public function teacherStudentCounts(): \Illuminate\Support\Collection
    {
        return app(TeacherStatsService::class)->buildTeacherStudentStats($this->teachers, $this->periodLessons);
    }

    #[Computed]
    public function trendData(): array
    {
        $months = range(1, 12);
        $stats = $this->yearStatsByMonth;

        // Monthly totals trend
        $monthlyTotals = collect($months)->map(fn ($m) => $stats[$this->year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT)]['total'] ?? 0)->toArray();

        // Completion rate trend
        $completionRates = collect($months)->map(function ($m) use ($stats) {
            $key = $this->year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $s = $stats[$key] ?? null;
            if (!$s || $s['total'] === 0) return 0;
            return round(($s['completed'] / $s['total']) * 100);
        })->toArray();

        // Cancellation trend (student + teacher cancelled)
        $cancellations = collect($months)->map(function ($m) use ($stats) {
            $key = $this->year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $s = $stats[$key] ?? null;
            if (!$s) return 0;
            return ($s['student_cancelled'] ?? 0) + ($s['teacher_cancelled'] ?? 0);
        })->toArray();

        return [
            'labels' => collect($months)->map(fn ($m) => \Carbon\Carbon::create($this->year, $m, 1)->format('M'))->toArray(),
            'totals' => $monthlyTotals,
            'completionRates' => $completionRates,
            'cancellations' => $cancellations,
        ];
    }

    #[Computed]
    public function chartData(): array
    {
        $teacherStats = $this->teacherStats->toArray();
        $studentStats = $this->studentStats->toArray();
        $teachers = $this->teachers->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()->toArray();
        $students = $this->students->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->toArray();

        return [
            'trend' => $this->trendData,
            'teacherStats' => $teacherStats,
            'studentStats' => $studentStats,
            'teachers' => $teachers,
            'students' => $students,
        ];
    }

    public function navigateMonth(int $year, int $month): void
    {
        $this->year = $year;
        $this->month = $month;
    }
};
?>

<div class="p-6 w-full mx-auto" x-data x-init="$wire.loadBalances()">
    <x-page-header title="Billing & Stats" :logoutRoute="route('admin.logout')">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-600 hover:underline">Calendar</a>
            <a href="{{ route('admin.logs') }}" class="text-sm text-blue-600 hover:underline">Activity Logs</a>
        </div>
    </x-page-header>

    <x-session-alert />

    <x-card class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold">{{ $this->currentMonth->format('F Y') }}</h2>
                <div class="flex items-center gap-1">
                    <button wire:click="navigateMonth({{ $this->prevMonth->year }}, {{ $this->prevMonth->month }})"
                            class="px-2 py-1 text-gray-600 hover:bg-gray-100 rounded">
                        &larr;
                    </button>
                    <button wire:click="navigateMonth({{ now()->year }}, {{ now()->month }})"
                            class="px-2 py-1 text-xs text-gray-600 hover:bg-gray-100 rounded">
                        Today
                    </button>
                    <button wire:click="navigateMonth({{ $this->nextMonth->year }}, {{ $this->nextMonth->month }})"
                            class="px-2 py-1 text-gray-600 hover:bg-gray-100 rounded">
                        &rarr;
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="$set('billing', false)"
                            class="px-3 py-1 text-xs rounded {{ !$billing ? 'bg-blue-100 text-blue-700' : 'text-gray-600 bg-gray-100' }}">
                        Calendar
                    </button>
                    <button wire:click="$set('billing', true)"
                            class="px-3 py-1 text-xs rounded {{ $billing ? 'bg-blue-100 text-blue-700' : 'text-gray-600 bg-gray-100' }}">
                        26-25
                    </button>
                </div>
                <span wire:loading class="text-sm text-gray-500">Loading...</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-status-legend compact />
                <button wire:click="refreshBalances"
                        wire:loading.attr="disabled"
                        class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                        title="Refresh balance data from Google Sheets">
                    <span wire:loading.remove wire:target="refreshBalances">Refresh Balance</span>
                    <span wire:loading wire:target="refreshBalances">Refreshing...</span>
                </button>
                <form method="POST" action="{{ route('admin.billing.export') }}" class="inline">
                    @csrf
                    <input type="hidden" name="billing" value="{{ $billing ? 1 : 0 }}">
                    <input type="hidden" name="year" value="{{ $this->currentMonth->year }}">
                    <input type="hidden" name="month" value="{{ $this->currentMonth->month }}">
                    <button type="submit" class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                        Export to Sheet
                    </button>
                </form>
            </div>
        </div>
    </x-card>

    {{-- Trend Charts --}}
    <div class="mb-3" wire:ignore>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-1.5">
            {{-- Lessons Trend --}}
            <div class="bg-white p-2 rounded border border-gray-200">
                <div class="text-[10px] text-gray-500 mb-1 font-medium">Lessons/Month</div>
                <div class="h-[120px]"><canvas id="lessonsTrendChart"></canvas></div>
            </div>
            {{-- Completion Rate Trend --}}
            <div class="bg-white p-2 rounded border border-gray-200">
                <div class="text-[10px] text-gray-500 mb-1 font-medium">Completion Rate %</div>
                <div class="h-[120px]"><canvas id="completionTrendChart"></canvas></div>
            </div>
            {{-- Cancellations Trend --}}
            <div class="bg-white p-2 rounded border border-gray-200">
                <div class="text-[10px] text-gray-500 mb-1 font-medium">Cancellations</div>
                <div class="h-[120px]"><canvas id="cancellationsTrendChart"></canvas></div>
            </div>
            {{-- Teachers Workload --}}
            <div class="bg-white p-2 rounded border border-gray-200">
                <div class="text-[10px] text-gray-500 mb-1 font-medium">Teacher Workload</div>
                <div class="h-[120px]"><canvas id="teacherWorkloadChart"></canvas></div>
            </div>
            {{-- Top Students --}}
            <div class="bg-white p-2 rounded border border-gray-200">
                <div class="text-[10px] text-gray-500 mb-1 font-medium">Top Students</div>
                <div class="h-[120px]"><canvas id="studentActivityChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6" wire:loading.class="opacity-50">
        <x-card title="Students">
            @if(!$balancesLoaded)
                <div class="p-4 text-center text-gray-500">
                    <span class="animate-pulse">Loading balance data...</span>
                </div>
            @endif
            <x-student-stats-list :students="$this->students" :stats="$this->studentStats" :totalStats="$this->periodStats" :showBalance="$balancesLoaded" />
        </x-card>

        <x-card title="Teachers">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Teacher</th>
                        <th class="px-3 py-2 text-right">Stats</th>
                        <th class="px-3 py-2 text-left">Students</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->teachers as $teacher)
                        @php
                            $ts = $this->teacherStats[$teacher->id] ?? ['total' => 0, 'completed' => 0, 'student_cancelled' => 0, 'teacher_cancelled' => 0, 'student_absent' => 0];
                            $studentCounts = $this->teacherStudentCounts[$teacher->id] ?? collect();
                        @endphp
                        <tr class="border-t align-top" wire:key="teacher-{{ $teacher->id }}">
                            <td class="px-3 py-2 whitespace-nowrap">{{ $teacher->name }}</td>
                            <td class="px-3 py-2 text-right align-top">
                                <x-stats-inline :stats="$ts" class="w-24 ml-auto text-gray-500" />
                            </td>
                            <td class="px-3 py-2 text-[11px] text-gray-700">
                                @if($studentCounts->isEmpty())
                                    <span class="text-gray-400">–</span>
                                @else
                                    <div class="space-y-1">
                                        @foreach($studentCounts as $sc)
                                            @php
                                                $scStats = array_merge([
                                                    'completed' => 0,
                                                    'student_cancelled' => 0,
                                                    'teacher_cancelled' => 0,
                                                    'student_absent' => 0,
                                                ], $sc['stats'] ?? []);
                                            @endphp
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="truncate">{{ $sc['name'] }}</span>
                                                <x-stats-inline :stats="$scStats" class="w-16 text-gray-500" />
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    </div>

    <x-stats-by-month-table
        :title="'Students by Month (' . $this->currentMonth->year . ')'"
        :entities="$this->students"
        :statsByEntity="$this->studentMonthStats"
        :months="range(1, 12)"
        :year="$this->currentMonth->year"
    />

    <x-stats-by-month-table
        :title="'Teachers by Month (' . $this->currentMonth->year . ')'"
        :entities="$this->teachers"
        :statsByEntity="$this->teacherMonthStats"
        :months="range(1, 12)"
        :year="$this->currentMonth->year"
    />

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartData = @json($this->chartData);
        const trendData = chartData.trend;
        const teacherStats = chartData.teacherStats;
        const studentStats = chartData.studentStats;
        const teachers = chartData.teachers;
        const students = chartData.students;

        const lineOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { font: { size: 9 } }, grid: { color: '#f3f4f6' } },
                x: { ticks: { font: { size: 9 } }, grid: { display: false } }
            },
            elements: { line: { tension: 0.3 }, point: { radius: 2 } }
        };

        const barOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { font: { size: 9 } }, grid: { display: false } },
                x: { ticks: { font: { size: 9 } }, grid: { display: false } }
            }
        };

        // Lessons Trend
        new Chart(document.getElementById('lessonsTrendChart'), {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [{
                    data: trendData.totals,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true
                }]
            },
            options: lineOptions
        });

        // Completion Rate Trend
        new Chart(document.getElementById('completionTrendChart'), {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [{
                    data: trendData.completionRates,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true
                }]
            },
            options: {...lineOptions, scales: {...lineOptions.scales, y: {...lineOptions.scales.y, max: 100}}}
        });

        // Cancellations Trend
        new Chart(document.getElementById('cancellationsTrendChart'), {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [{
                    data: trendData.cancellations,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true
                }]
            },
            options: lineOptions
        });

        // Teacher Workload Bar
        const teacherData = Object.entries(teacherStats)
            .map(([id, stats]) => ({
                name: teachers.find(t => t.id === parseInt(id))?.name?.split(' ')[0] || '?',
                value: stats.completed || 0
            }))
            .sort((a, b) => b.value - a.value);

        new Chart(document.getElementById('teacherWorkloadChart'), {
            type: 'bar',
            data: {
                labels: teacherData.map(t => t.name),
                datasets: [{ data: teacherData.map(t => t.value), backgroundColor: 'rgba(99, 102, 241, 0.8)' }]
            },
            options: barOptions
        });

        // Top Students Bar
        const studentData = Object.entries(studentStats)
            .map(([id, stats]) => ({
                name: students.find(s => s.id === parseInt(id))?.name?.split(' ')[0] || '?',
                value: stats.completed || 0
            }))
            .sort((a, b) => b.value - a.value)
            .slice(0, 8);

        new Chart(document.getElementById('studentActivityChart'), {
            type: 'bar',
            data: {
                labels: studentData.map(s => s.name),
                datasets: [{ data: studentData.map(s => s.value), backgroundColor: 'rgba(236, 72, 153, 0.8)' }]
            },
            options: barOptions
        });
    });
    </script>
</div>
