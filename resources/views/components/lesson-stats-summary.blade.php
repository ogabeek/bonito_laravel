@props(['stats', 'date', 'prevMonth', 'nextMonth', 'routeName', 'routeParams' => []])

<x-card class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">{{ $date->format('F Y') }}</h2>
        <x-month-nav 
            :currentMonth="$date" 
            :prevMonth="$prevMonth" 
            :nextMonth="$nextMonth" 
            :routeName="$routeName" 
            :routeParams="$routeParams" 
        />
    </div>
    <div class="flex justify-center gap-4 mt-3 border-t pt-3">
        <div class="flex items-center gap-2">
            <span class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</span>
            <span class="text-sm text-gray-500">Total</span>
        </div>
        <div class="border-l border-gray-300"></div>
        <div class="flex items-center gap-2">
            <span class="text-xl font-semibold" style="color: var(--color-status-completed);">{{ $stats['completed'] }}</span>
            <span class="text-sm text-gray-600">Completed</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xl font-semibold" style="color: var(--color-status-absent);">{{ $stats['student_absent'] }}</span>
            <span class="text-sm text-gray-600">Absent</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xl font-semibold" style="color: var(--color-status-student-cancelled);">{{ $stats['student_cancelled'] }}</span>
            <span class="text-sm text-gray-600">Student Cancelled</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xl font-semibold" style="color: var(--color-status-cancelled);">{{ $stats['teacher_cancelled'] }}</span>
            <span class="text-sm text-gray-600">Cancelled</span>
        </div>
    </div>
</x-card>
