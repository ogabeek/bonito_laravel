@extends('layouts.app', ['favicon' => 'favicon-student.svg'])

@section('title', $student->name . "'s Lessons")

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    
    <x-page-header 
        :title="$student->name" 
        :subtitle="$student->goal ? 'Goal: ' . $student->goal : null" 
    />

    <x-card class="mb-6">
        <div class="flex justify-center gap-4">
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
                <span class="text-xl font-semibold" style="color: var(--color-status-student-cancelled);">{{ $stats['student_cancelled'] }}</span>
                <span class="text-sm text-gray-600">Student Cancelled</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xl font-semibold" style="color: var(--color-status-cancelled);">{{ $stats['teacher_cancelled'] }}</span>
                <span class="text-sm text-gray-600">Teacher Cancelled</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xl font-semibold" style="color: var(--color-status-absent);">{{ $stats['student_absent'] }}</span>
                <span class="text-sm text-gray-600">Absent</span>
            </div>
        </div>
    </x-card>

    @if($upcomingLessons->count() > 0)
        <x-card title="📅 Upcoming Lessons" class="mb-6">
            <div class="space-y-3">
                    @foreach($upcomingLessons as $lesson)
                        <div class="border-l-4 border-blue-500 pl-4 py-2 bg-blue-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{ $lesson->class_date->format('l, F d, Y') }}
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        Teacher: {{ $lesson->teacher->name }}
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $lesson->class_date->format('g:i A') }}
                                </div>
                            </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    <x-card :title="'📚 Past Lessons (' . $pastLessons->flatten()->count() . ')'">
        @if($pastLessons->isNotEmpty())
            <div class="space-y-4">
                @foreach($pastLessons as $month => $lessons)
                    @php
                        [$year, $monthNum] = explode('-', $month);
                        $monthName = \Carbon\Carbon::createFromDate($year, $monthNum, 1)->format('F Y');
                    @endphp
                    <div>
                        <div class="text-sm font-semibold text-gray-700 mb-2">{{ $monthName }}</div>
                        <div class="space-y-2">
                            @foreach($lessons as $lesson)
                                <x-lesson-card :lesson="$lesson" :showTeacher="true" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <x-empty-state message="No lessons yet" />
        @endif
    </x-card>

</div>
@endsection
