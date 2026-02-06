@props(['stats', 'date', 'prevMonth', 'nextMonth', 'routeName', 'routeParams' => [], 'showNav' => true, 'title' => null])

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
    <div class="flex justify-center gap-2 sm:gap-4 mt-3 border-t pt-3 text-xs sm:text-base">
        <div class="flex items-center gap-1 sm:gap-2">
            <span class="text-lg sm:text-2xl font-bold text-gray-800">{{ $stats['total'] }}</span>
            <span class="text-gray-500">Total</span>
        </div>
        <div class="border-l border-gray-300"></div>
        <div class="flex items-center gap-1 sm:gap-2">
            <span class="text-base sm:text-xl font-semibold" style="color: var(--color-status-completed);">{{ $stats['completed'] }}</span>
            <span class="text-gray-600">Done</span>
        </div>
        <div class="flex items-center gap-1 sm:gap-2">
            <span class="text-base sm:text-xl font-semibold" style="color: var(--color-status-absent);">{{ $stats['student_absent'] }}</span>
            <span class="text-gray-600">Abs</span>
        </div>
        <div class="flex items-center gap-1 sm:gap-2">
            <span class="text-base sm:text-xl font-semibold" style="color: var(--color-status-student-cancelled);">{{ $stats['student_cancelled'] }}</span>
            <span class="text-gray-600">SC</span>
        </div>
        <div class="flex items-center gap-1 sm:gap-2">
            <span class="text-base sm:text-xl font-semibold" style="color: var(--color-status-cancelled);">{{ $stats['teacher_cancelled'] }}</span>
            <span class="text-gray-600">TC</span>
        </div>
    </div>
</x-card>
