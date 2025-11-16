@props(['date', 'stats'])

<div class="bg-white p-4 rounded-lg shadow mb-6">
    <div class="flex justify-between items-center">
        <a href="?month={{ $date->copy()->subMonth()->format('Y-m') }}" class="text-blue-600 hover:underline">
            ← {{ $date->copy()->subMonth()->format('M') }}
        </a>
        <h2 class="text-xl font-semibold">{{ $date->format('F Y') }}</h2>
        <a href="?month={{ $date->copy()->addMonth()->format('Y-m') }}" class="text-blue-600 hover:underline">
            {{ $date->copy()->addMonth()->format('M') }} →
        </a>
    </div>
    
    @if($stats)
        <div class="flex justify-center gap-4 mt-3">
            <div class="flex items-center gap-2">
                <span class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</span>
                <span class="text-sm text-gray-500">Total</span>
            </div>
            <div class="border-l border-gray-300"></div>
            <div class="flex items-center gap-2">
                <span class="text-xl font-semibold text-green-600">{{ $stats['completed'] }}</span>
                <span class="text-sm text-gray-600">Completed</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xl font-semibold text-orange-600">{{ $stats['student_absent'] }}</span>
                <span class="text-sm text-gray-600">Absent</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xl font-semibold text-red-600">{{ $stats['teacher_cancelled'] }}</span>
                <span class="text-sm text-gray-600">Cancelled</span>
            </div>
        </div>
    @endif
</div>
