@props([
    'stats',
    'date',
    'prevMonth',
    'nextMonth',
    'routeName',
    'routeParams' => [],
    'showNav' => true,
    'title' => null,
    'friendlyLabels' => false,
])

@php
    $labels = $friendlyLabels
        ? [
            'completed' => 'Completed',
            'student_absent' => 'Missed',
            'student_cancelled' => 'Student cancelled',
            'teacher_cancelled' => 'Teacher cancelled',
        ]
        : [
            'completed' => 'Done',
            'student_absent' => 'Abs',
            'student_cancelled' => 'SC',
            'teacher_cancelled' => 'TC',
        ];
@endphp

<x-card class="mb-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 sm:gap-0 mb-4">
        <h2 class="text-lg sm:text-xl font-semibold">{{ $title ?? $date->format('F Y') }}</h2>
        @if($showNav)
            <x-month-nav
                :currentMonth="$date"
                :prevMonth="$prevMonth"
                :nextMonth="$nextMonth"
                :routeName="$routeName"
                :routeParams="$routeParams"
            />
        @endif
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-3 border-t pt-3 text-xs sm:text-sm">
        <div class="flex flex-col items-center gap-1">
            <span class="text-xl sm:text-2xl font-bold text-gray-800">{{ $stats['total'] }}</span>
            <span class="text-gray-500">Total</span>
        </div>
        <div class="flex flex-col items-center gap-1">
            <span class="text-lg sm:text-xl font-semibold" style="color: var(--color-status-completed);">{{ $stats['completed'] }}</span>
            <span class="text-center text-gray-600">{{ $labels['completed'] }}</span>
        </div>
        <div class="flex flex-col items-center gap-1">
            <span class="text-lg sm:text-xl font-semibold" style="color: var(--color-status-absent);">{{ $stats['student_absent'] }}</span>
            <span class="text-center text-gray-600">{{ $labels['student_absent'] }}</span>
        </div>
        <div class="flex flex-col items-center gap-1">
            <span class="text-lg sm:text-xl font-semibold" style="color: var(--color-status-student-cancelled);">{{ $stats['student_cancelled'] }}</span>
            <span class="text-center text-gray-600">{{ $labels['student_cancelled'] }}</span>
        </div>
        <div class="flex flex-col items-center gap-1">
            <span class="text-lg sm:text-xl font-semibold" style="color: var(--color-status-cancelled);">{{ $stats['teacher_cancelled'] }}</span>
            <span class="text-center text-gray-600">{{ $labels['teacher_cancelled'] }}</span>
        </div>
    </div>
</x-card>
