@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Billing & Stats')


@section('content')
<div class="p-6 w-full mx-auto">
    <x-page-header title="Billing & Stats" :logoutRoute="route('admin.logout')">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-600 hover:underline">Calendar</a>
    </x-page-header>

    <x-card class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold">{{ $currentMonth->format('F Y') }}</h2>
                <x-month-nav 
                    :currentMonth="$currentMonth" 
                    :prevMonth="$prevMonth" 
                    :nextMonth="$nextMonth" 
                    routeName="admin.billing" 
                    :routeParams="['billing' => $billing ? 1 : null]" 
                />
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.billing', ['year' => $currentMonth->year, 'month' => $currentMonth->month]) }}" class="px-3 py-1 text-xs rounded {{ $billing ? 'text-gray-600 bg-gray-100' : 'bg-blue-100 text-blue-700' }}">Calendar</a>
                    <a href="{{ route('admin.billing', ['year' => $currentMonth->year, 'month' => $currentMonth->month, 'billing' => 1]) }}" class="px-3 py-1 text-xs rounded {{ $billing ? 'bg-blue-100 text-blue-700' : 'text-gray-600 bg-gray-100' }}">26-25</a>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-status-legend compact />
                <form method="POST" action="{{ route('admin.billing.export') }}" class="ml-auto">
                    @csrf
                    <input type="hidden" name="billing" value="{{ $billing ? 1 : 0 }}">
                    <input type="hidden" name="year" value="{{ $currentMonth->year }}">
                    <input type="hidden" name="month" value="{{ $currentMonth->month }}">
                    <button type="submit" class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                        Export to Sheet
                    </button>
                </form>
            </div>
        </div>
    </x-card>

    {{-- Quick Stats Charts --}}
    <div class="grid grid-cols-2 gap-3 mb-6">
        <div class="bg-white p-3 rounded border border-gray-200">
            <div class="text-xs text-gray-500 mb-2">Teachers (completed lessons)</div>
            <canvas id="teacherWorkloadChart" height="120"></canvas>
        </div>
        <div class="bg-white p-3 rounded border border-gray-200">
            <div class="text-xs text-gray-500 mb-2">Top Students (completed lessons)</div>
            <canvas id="studentActivityChart" height="120"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <x-card title="Students">
            <x-student-stats-list :students="$students" :stats="$studentStats" :totalStats="$periodStats" :showBalance="true" />
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
                    @foreach($teachers as $teacher)
                        @php
                            $ts = $teacherStats[$teacher->id] ?? ['total' => 0, 'completed' => 0, 'student_cancelled' => 0, 'teacher_cancelled' => 0, 'student_absent' => 0];
                            $studentCounts = $teacherStudentCounts[$teacher->id] ?? collect();
                        @endphp
                        <tr class="border-t align-top">
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
        :title="'Students by Month (' . $currentMonth->year . ')'"
        :entities="$students"
        :statsByEntity="$studentMonthStats"
        :months="$months"
        :year="$currentMonth->year"
    />

    <x-stats-by-month-table 
        :title="'Teachers by Month (' . $currentMonth->year . ')'"
        :entities="$teachers"
        :statsByEntity="$teacherMonthStats"
        :months="$months"
        :year="$currentMonth->year"
    />

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { display: false, beginAtZero: true },
            x: { ticks: { font: { size: 10 } } }
        }
    };

    // Teachers Chart
    const teacherCtx = document.getElementById('teacherWorkloadChart');
    if (teacherCtx) {
        const teacherData = @json($teacherStats);
        const teachers = @json($teachers);

        const data = Object.entries(teacherData)
            .map(([id, stats]) => ({
                name: teachers.find(t => t.id === parseInt(id))?.name?.split(' ')[0] || '?',
                value: stats.completed || 0
            }))
            .sort((a, b) => b.value - a.value);

        new Chart(teacherCtx, {
            type: 'bar',
            data: {
                labels: data.map(t => t.name),
                datasets: [{ data: data.map(t => t.value), backgroundColor: 'rgba(99, 102, 241, 0.7)' }]
            },
            options: chartOptions
        });
    }

    // Students Chart (Top 8)
    const studentCtx = document.getElementById('studentActivityChart');
    if (studentCtx) {
        const studentData = @json($studentStats);
        const students = @json($students);

        const data = Object.entries(studentData)
            .map(([id, stats]) => ({
                name: students.find(s => s.id === parseInt(id))?.name?.split(' ')[0] || '?',
                value: stats.completed || 0
            }))
            .sort((a, b) => b.value - a.value)
            .slice(0, 8);

        new Chart(studentCtx, {
            type: 'bar',
            data: {
                labels: data.map(s => s.name),
                datasets: [{ data: data.map(s => s.value), backgroundColor: 'rgba(236, 72, 153, 0.7)' }]
            },
            options: chartOptions
        });
    }
});
</script>
@endpush

@endsection
