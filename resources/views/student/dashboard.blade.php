@extends('layouts.app', ['favicon' => 'favicon-student.svg'])

@section('title', $student->name . "'s Lessons")

@section('content')
<div class="p-3 sm:p-6 max-w-4xl mx-auto">
    
    <x-page-header 
        :title="$student->name" 
        :subtitle="$student->goal ? 'Goal: ' . $student->goal : null" 
    />

    @if($stats['total'] === 0)
        <x-info-banner type="success" dismissible class="mb-6">
            <div class="font-medium mb-1">Welcome! 🎉</div>
            <div class="text-xs opacity-90">
                This is your personal learning dashboard. After each lesson, your teacher will log what you covered, 
                homework assignments, and track your progress. Check back after your first class!
            </div>
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

    <x-card :title="'📚 Lessons (' . $pastLessons->flatten()->count() . ')'">
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
