@extends('layouts.app', ['favicon' => 'favicon-student.svg'])

@section('title', $student->name . "'s Lessons")

@section('content')
<div class="p-3 sm:p-6 max-w-4xl mx-auto">
    
    <x-page-header 
        :title="$student->name" 
        :subtitle="$student->goal ? 'Goal: ' . $student->goal : null" 
    />

    @if(config('banners.student_welcome.enabled') && $stats['total'] < 5)
        <x-info-banner :type="config('banners.student_welcome.type')" dismissible class="mb-6">
            <div class="font-medium mb-1">{{ config('banners.student_welcome.title') }}</div>
            <div class="text-xs opacity-90">{{ config('banners.student_welcome.message') }}</div>
        </x-info-banner>
    @endif

    @if(config('banners.student_info.enabled'))
        <x-info-banner :type="config('banners.student_info.type')" id="student_info" class="mb-6">
            {{ config('banners.student_info.message') }}
        </x-info-banner>
    @endif

    <x-lesson-stats-summary 
        :stats="$stats" 
        :date="now()" 
        :prevMonth="now()->copy()->subMonth()" 
        :nextMonth="now()->copy()->addMonth()" 
        routeName="student.dashboard" 
        :routeParams="['student' => $student]" 
        :showNav="false" 
        title="All Lessons"
    />

    <livewire:student-teacher-info :student="$student" />

    <livewire:student-teacher-notes :student="$student" />

    <x-card :title="'📚 Lessons (' . $lessonsByMonth->flatten()->count() . ')'" class="mt-6">
        @if($lessonsByMonth->isNotEmpty())
            <div class="space-y-4">
                @foreach($lessonsByMonth as $month => $lessons)
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
