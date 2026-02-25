<?php

use App\Enums\StudentStatus;
use App\Models\Teacher;
use App\Services\CalendarService;
use App\Services\DashboardDataService;
use Livewire\Attributes\Computed;
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

    public string $activeTab = 'calendar';
    public string $teacherFilter = '';
    public string $statusFilter = '';
    public bool $showAddTeacher = false;
    public bool $showAddStudent = false;

    public function mount(): void
    {
        $now = now();
        $this->year = $this->year ?? $now->year;
        $this->month = $this->month ?? $now->month;
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
    public function calendarDays(): array
    {
        $tailDay = config('billing.period_end_day', 25);
        $start = $this->currentMonth->copy()->subMonthNoOverflow()->day($tailDay);
        $end = $this->currentMonth->copy()->endOfMonth();

        $days = [];
        $current = $start->copy();
        while ($current <= $end) {
            $days[] = $current->copy();
            $current->addDay();
        }

        return $days;
    }

    #[Computed]
    public function students(): \Illuminate\Support\Collection
    {
        return app(DashboardDataService::class)->getStudents();
    }

    #[Computed]
    public function teachers(): \Illuminate\Support\Collection
    {
        return app(DashboardDataService::class)->getTeachers();
    }

    #[Computed]
    public function archivedTeachers(): \Illuminate\Support\Collection
    {
        return app(DashboardDataService::class)->getArchivedTeachers();
    }

    #[Computed]
    public function lessons(): \Illuminate\Support\Collection
    {
        return app(DashboardDataService::class)->getLessonsCollection($this->currentMonth, $this->billing);
    }

    #[Computed]
    public function lessonsThisMonth(): \Illuminate\Support\Collection
    {
        return app(DashboardDataService::class)->getLessonsForMonth($this->currentMonth);
    }

    #[Computed]
    public function periodStats(): array
    {
        return app(DashboardDataService::class)->calculateStats($this->lessons);
    }

    #[Computed]
    public function studentStats(): \Illuminate\Support\Collection
    {
        return app(DashboardDataService::class)->calculateStatsByStudent($this->lessons);
    }

    #[Computed]
    public function teacherStats(): \Illuminate\Support\Collection
    {
        return app(DashboardDataService::class)->calculateStatsByTeacher($this->lessons);
    }

    public function navigateMonth(int $year, int $month): void
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function toggleBilling(): void
    {
        $this->billing = ! $this->billing;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
};
?>

<div class="p-6 w-full mx-auto">
    <x-page-header title="Admin Dashboard" :logoutRoute="route('admin.logout')">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.billing') }}" class="text-sm text-blue-600 hover:underline">Billing / Stats</a>
            <a href="{{ route('admin.logs') }}" class="text-sm text-blue-600 hover:underline">Activity Logs</a>
        </div>
    </x-page-header>

    <x-error-list />

    <x-card>
        <div class="border-b flex gap-4 px-4">
            <button wire:click="setTab('calendar')"
                    class="py-3 font-medium {{ $activeTab === 'calendar' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600' }}">
                Calendar
            </button>
            <button wire:click="setTab('teachers')"
                    class="py-3 font-medium {{ $activeTab === 'teachers' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600' }}">
                Teachers
            </button>
        </div>

        <div class="p-6">
            {{-- Calendar Tab --}}
            @if($activeTab === 'calendar')
                <div wire:loading.delay.class="opacity-50">
                    <div class="flex justify-between items-start gap-4 mb-4">
                        <div class="flex items-center gap-4">
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
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="grid grid-cols-5 gap-3 text-xs text-gray-600 bg-gray-50 rounded px-3 py-2">
                                <div class="text-center">
                                    <div class="font-semibold" style="color: var(--color-status-completed);">{{ $this->periodStats['completed'] }}</div>
                                    <div>Done</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-semibold" style="color: var(--color-status-student-cancelled);">{{ $this->periodStats['student_cancelled'] }}</div>
                                    <div>C</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-semibold" style="color: var(--color-status-cancelled);">{{ $this->periodStats['teacher_cancelled'] }}</div>
                                    <div>CT</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-semibold" style="color: var(--color-status-absent);">{{ $this->periodStats['student_absent'] }}</div>
                                    <div>A</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-semibold text-gray-900">{{ $this->periodStats['total'] }}</div>
                                    <div>Total</div>
                                </div>
                            </div>
                            <button wire:click="$toggle('showAddStudent')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                {{ $showAddStudent ? 'Cancel' : '+ Add Student' }}
                            </button>
                        </div>
                    </div>

                    {{-- Add Student Form --}}
                    @if($showAddStudent)
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <form method="POST" action="{{ route('admin.students.store') }}">
                                @csrf
                                <x-student-form mode="create" />
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 mt-3">Create Student</button>
                            </form>
                        </div>
                    @endif

                    {{-- Filters --}}
                    <div class="mb-4 flex gap-3">
                        <select wire:model.live="teacherFilter" class="pl-3 pr-8 py-2 border rounded">
                            <option value="">All Teachers</option>
                            @foreach($this->teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="statusFilter" class="pl-3 pr-8 py-2 border rounded">
                            <option value="">All Statuses</option>
                            @foreach(StudentStatus::cases() as $statusOption)
                                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                        <span wire:loading class="text-sm text-gray-500 self-center">Loading...</span>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                        <div class="xl:col-span-4 overflow-x-auto border rounded">
                            <table class="w-full text-sm cal-table"
                                   x-data="{ hoverCol: -1 }"
                                   @mouseleave="hoverCol = -1">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="cal-cell cal-sticky border-r bg-gray-50 min-w-[170px] text-sm">Student</th>
                                        @foreach($this->calendarDays as $date)
                                            @php
                                                $isPrevMonth = $date->month !== $this->currentMonth->month;
                                                $isWeekend = $date->isWeekend();
                                                $isToday = $date->isToday();
                                            @endphp
                                            <th class="cal-cell cal-day border-l {{ $isPrevMonth ? 'bg-gray-200 text-gray-400' : '' }} {{ $isWeekend && !$isPrevMonth ? 'bg-gray-100' : '' }} {{ $isToday ? 'bg-blue-50' : '' }}"
                                                @mouseenter="hoverCol = {{ $loop->index }}"
                                                :class="hoverCol === {{ $loop->index }} && 'cal-col-hover'">
                                                <div class="cal-daynum">{{ $date->day }}</div>
                                                <div class="cal-weekday">{{ substr($date->format('D'), 0, 2) }}</div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->students as $student)
                                        @php
                                            $matchesTeacher = $teacherFilter === '' || in_array((int) $teacherFilter, $student->teacher_ids);
                                            $matchesStatus = $statusFilter === '' || $statusFilter === $student->status->value;
                                        @endphp
                                        @if($matchesTeacher && $matchesStatus)
                                            <tr class="border-t hover:bg-gray-50" wire:key="student-{{ $student->id }}">
                                                <td class="cal-cell cal-sticky border-r bg-white align-middle">
                                                    <div class="flex items-center gap-1 min-w-0">
                                                        <x-student-status-dot :status="$student->status" />
                                                        <a href="{{ route('admin.students.edit', $student) }}" class="font-medium text-[12px] text-gray-900 hover:text-blue-600 truncate">
                                                            {{ $student->name }}
                                                        </a>
                                                        <x-student-stats-compact :stats="($this->studentStats[$student->id] ?? null)" class="w-16 ml-auto text-gray-500" />
                                                    </div>
                                                    @if($student->teachers->count() > 0)
                                                        <div class="text-xs text-gray-500 ml-3.5">{{ $student->teachers->pluck('name')->join(', ') }}</div>
                                                    @endif
                                                </td>
                                                @foreach($this->calendarDays as $date)
                                                    @php
                                                        $isPrevMonth = $date->month !== $this->currentMonth->month;
                                                        $dateKey = $student->id . '_' . $date->format('Y-m-d');
                                                        $dayLessons = $this->lessonsThisMonth->get($dateKey, collect());
                                                        $isWeekend = $date->isWeekend();
                                                        $isToday = $date->isToday();
                                                    @endphp
                                                    <td class="cal-cell cal-day cal-daycell border-l {{ $isPrevMonth ? 'bg-gray-100' : '' }} {{ $isWeekend && !$isPrevMonth ? 'bg-gray-50' : '' }} {{ $isToday ? 'bg-blue-50' : '' }}"
                                                        @mouseenter="hoverCol = {{ $loop->index }}"
                                                        :class="hoverCol === {{ $loop->index }} && 'cal-col-hover'">
                                                        <div class="flex flex-wrap justify-center gap-0.5">
                                                            @foreach($dayLessons as $lesson)
                                                                <span class="cal-lesson-chip"
                                                                     style="background: var(--color-status-{{ $lesson->status->cssClass() }}-bg); color: var(--color-status-{{ $lesson->status->cssClass() }});"
                                                                     title="{{ $lesson->teacher->name }} - {{ $lesson->status->label() }}">
                                                                    {{ substr($lesson->teacher->name, 0, 1) }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-status-legend />
                    </div>
                </div>
            @endif

            {{-- Teachers Tab --}}
            @if($activeTab === 'teachers')
                <div wire:loading.delay.class="opacity-50">
                    {{-- Add Teacher Form --}}
                    @if($showAddTeacher)
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <form method="POST" action="{{ route('admin.teachers.create') }}" class="flex gap-4 items-end">
                                @csrf
                                <x-teacher-form mode="create" />
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create</button>
                            </form>
                        </div>
                    @endif

                    {{-- Teachers Table --}}
                    <table class="w-full border rounded">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Teacher</th>
                                <th class="px-4 py-2 text-left">Students</th>
                                <th class="px-4 py-2 text-left">Lessons</th>
                                <th class="px-4 py-2 text-right">Month</th>
                                <th class="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->teachers as $teacher)
                                <tr class="border-t hover:bg-gray-50" wire:key="teacher-{{ $teacher->id }}">
                                    <td class="px-4 py-2">{{ $teacher->name }}</td>
                                    <td class="px-4 py-2">{{ $teacher->students_count }}</td>
                                    <td class="px-4 py-2">{{ $teacher->lessons_count }}</td>
                                    @php
                                        $ts = $this->teacherStats[$teacher->id] ?? ['total' => 0, 'completed' => 0, 'student_cancelled' => 0, 'teacher_cancelled' => 0, 'student_absent' => 0];
                                    @endphp
                                    <td class="px-4 py-2 text-right">
                                        <x-stats-inline :stats="$ts" class="w-20 ml-auto text-gray-500" />
                                    </td>
                                    <td class="px-4 py-2 text-right flex gap-2 justify-end">
                                        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                                        <form method="POST" action="{{ route('admin.teachers.delete', $teacher) }}" onsubmit="return confirm('Archive {{ $teacher->name }}? (Can be restored later)')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-orange-600 hover:text-orange-800">Archive</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="flex justify-end items-center mt-4">
                        <button wire:click="$toggle('showAddTeacher')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            {{ $showAddTeacher ? 'Cancel' : '+ Add Teacher' }}
                        </button>
                    </div>

                    {{-- Archived Teachers Section --}}
                    @if($this->archivedTeachers->count() > 0)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold mb-4 text-gray-700">Archived Teachers</h3>
                            <div class="space-y-2">
                                @foreach($this->archivedTeachers as $teacher)
                                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded" wire:key="archived-{{ $teacher->id }}">
                                        <span class="text-gray-600">{{ $teacher->name }}</span>
                                        <form method="POST" action="{{ route('admin.teachers.restore', $teacher->id) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-card>
</div>
