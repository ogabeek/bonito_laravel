@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Billing & Stats')

@push('styles')
<style>
    canvas {
        height: 140px !important;
    }
</style>
@endpush

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
    <div class="mb-3">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-1.5">
            <div class="bg-white p-2 rounded border border-gray-200">
                <canvas id="periodOverviewChart"></canvas>
            </div>
            <div class="bg-white p-2 rounded border border-gray-200">
                <canvas id="cancellationChart"></canvas>
            </div>
            <div class="bg-white p-2 rounded border border-gray-200">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
            <div class="bg-white p-2 rounded border border-gray-200">
                <canvas id="teacherWorkloadChart"></canvas>
            </div>
            <div class="bg-white p-2 rounded border border-gray-200">
                <canvas id="studentActivityChart"></canvas>
            </div>
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
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        layout: {
            padding: 0
        }
    };

    // Period Overview Chart (Doughnut)
    const periodCtx = document.getElementById('periodOverviewChart');
    if (periodCtx) {
        const total = {{ $periodStats['total'] ?? 0 }};
        const completed = {{ $periodStats['completed'] ?? 0 }};
        new Chart(periodCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Other'],
                datasets: [{
                    data: [completed, total - completed],
                    backgroundColor: ['rgba(34, 197, 94, 0.8)', 'rgba(229, 231, 235, 0.5)'],
                    borderWidth: 0
                }]
            },
            options: {
                ...chartDefaults,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: completed + '/' + total,
                        font: { size: 8 },
                        padding: 0
                    }
                }
            }
        });
    }

    // Cancellation Breakdown
    const cancelCtx = document.getElementById('cancellationChart');
    if (cancelCtx) {
        new Chart(cancelCtx, {
            type: 'bar',
            data: {
                labels: ['Std', 'Tch'],
                datasets: [{
                    label: 'Cancelled',
                    data: [
                        {{ $periodStats['student_cancelled'] ?? 0 }},
                        {{ $periodStats['teacher_cancelled'] ?? 0 }}
                    ],
                    backgroundColor: ['rgba(251, 191, 36, 0.8)', 'rgba(239, 68, 68, 0.8)'],
                    borderWidth: 0
                }]
            },
            options: {
                ...chartDefaults,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Cancel',
                        font: { size: 8 },
                        padding: 0
                    }
                },
                scales: {
                    y: {
                        display: false,
                        beginAtZero: true
                    },
                    x: {
                        ticks: { font: { size: 7 }, padding: 0 }
                    }
                }
            }
        });
    }

    // Monthly Trend Chart (Sparkline)
    const monthlyCtx = document.getElementById('monthlyTrendChart');
    if (monthlyCtx) {
        const monthlyData = @json($yearStatsByMonth ?? []);
        console.log('Monthly data:', monthlyData);
        
        const data = [];
        for (let i = 1; i <= 12; i++) {
            data.push(monthlyData[i]?.completed || 0);
        }
        console.log('Chart data:', data);
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: ['J','F','M','A','M','J','J','A','S','O','N','D'],
                datasets: [{
                    data: data,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2,
                    borderWidth: 2
                }]
            },
            options: {
                ...chartDefaults,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Trend',
                        font: { size: 8 },
                        padding: 0
                    }
                },
                scales: {
                    y: {
                        display: true,
                        beginAtZero: true,
                        ticks: { 
                            font: { size: 6 },
                            stepSize: 50,
                            padding: 2
                        },
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        ticks: { font: { size: 6 }, padding: 0 },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Teacher Workload Chart
    const teacherCtx = document.getElementById('teacherWorkloadChart');
    if (teacherCtx) {
        const teacherData = @json($teacherStats);
        const teachers = @json($teachers);
        
        const teacherArray = Object.entries(teacherData)
            .map(([id, stats]) => ({
                name: teachers.find(t => t.id === parseInt(id))?.name?.split(' ')[0] || '?',
                completed: stats.completed || 0
            }))
            .sort((a, b) => b.completed - a.completed);

        new Chart(teacherCtx, {
            type: 'bar',
            data: {
                labels: teacherArray.map(t => t.name),
                datasets: [{
                    data: teacherArray.map(t => t.completed),
                    backgroundColor: 'rgba(168, 85, 247, 0.8)',
                    borderWidth: 0
                }]
            },
            options: {
                ...chartDefaults,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Teachers',
                        font: { size: 8 },
                        padding: 0
                    }
                },
                scales: {
                    y: {
                        display: false,
                        beginAtZero: true
                    },
                    x: {
                        ticks: { font: { size: 6 }, padding: 0 }
                    }
                }
            }
        });
    }

    // Student Activity Chart (Top Active)
    const studentCtx = document.getElementById('studentActivityChart');
    if (studentCtx) {
        const studentData = @json($studentStats);
        const students = @json($students);
        
        const studentArray = Object.entries(studentData)
            .map(([id, stats]) => ({
                name: students.find(s => s.id === parseInt(id))?.name?.split(' ')[0] || '?',
                completed: stats.completed || 0
            }))
            .sort((a, b) => b.completed - a.completed)
            .slice(0, 5);

        new Chart(studentCtx, {
            type: 'bar',
            data: {
                labels: studentArray.map(s => s.name),
                datasets: [{
                    data: studentArray.map(s => s.completed),
                    backgroundColor: 'rgba(236, 72, 153, 0.8)',
                    borderWidth: 0
                }]
            },
            options: {
                ...chartDefaults,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Students',
                        font: { size: 8 },
                        padding: 0
                    }
                },
                scales: {
                    y: {
                        display: false,
                        beginAtZero: true
                    },
                    x: {
                        ticks: { font: { size: 6 }, padding: 0 }
                    }
                }
            }
        });
    }
});
</script>
@endpush

@endsection
