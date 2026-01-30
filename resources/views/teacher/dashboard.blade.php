{{--
    * VIEW: Teacher Dashboard
    * Shows: month stats, lesson form, lesson list
    * Variables: $teacher, $students, $lessons, $stats, $studentStats, $date
--}}
@extends('layouts.app', ['favicon' => 'favicon-teacher.svg'])

@section('title', $teacher->name . "'s Dashboard")

@section('content')
<div class="p-3 sm:p-6 max-w-5xl mx-auto">
    
    {{-- * Reusable component with title + logout button --}}
    <x-page-header 
        :title="$teacher->name . \"'s Dashboard\"" 
        :logoutRoute="route('teacher.logout')" 
    />

    {{-- * Shows validation errors from session --}}
    <x-error-list />

    @php
        $now = now();
        $maintenanceStart = \Carbon\Carbon::parse('2026-01-30 00:00:00');
        $maintenanceEnd = \Carbon\Carbon::parse('2026-02-01 00:00:00');
        $isMaintenanceMode = $now->between($maintenanceStart, $maintenanceEnd);
    @endphp

    @if($isMaintenanceMode)
        <x-info-banner type="warning" icon="🎓" class="mb-6">
            <strong>Demo/Presentation Mode Active</strong>
            <p class="mt-1 text-sm">
                The platform is being showcased today. All features work normally! 
                Back to regular operations on <strong>February 1st</strong>.
            </p>
        </x-info-banner>
    @endif

    <x-info-banner type="tip" dismissible class="mb-6">
        <div class="font-medium mb-1">📝 How to use this page</div>
        <div class="text-xs opacity-90">
            <strong>Mark a lesson:</strong> Use the form below to select a student, mark attendance (Done/C - Canceled /CT - Canceled by the Teacher /Absent (when student didn't appear withou any notifications (we need to inform parents in this case), add topic and homework.<br>
            <strong>Quick tip:</strong> Click on any student's name in the list above to jump to their personal page.
        </div>
    </x-info-banner>

    {{-- * Student stats grid with month navigation --}}
    <x-card class="mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start gap-3 sm:gap-4 mb-3">
            <div class="flex items-center gap-2 sm:gap-3">
                <h2 class="text-lg sm:text-xl font-semibold">{{ $date->format('F Y') }}</h2>
                <x-month-nav 
                    :currentMonth="$date" 
                    :prevMonth="$prevMonth" 
                    :nextMonth="$nextMonth" 
                    routeName="teacher.dashboard" 
                    :routeParams="['teacher' => $teacher->id]" 
                />
            </div>
        </div>
        @if($students->count() > 0)
            <x-student-stats-list :students="$students" :stats="$studentStats" :totalStats="$stats" :showBalance="false" />
        @else
            <x-empty-state message="No students assigned" />
        @endif
    </x-card>

    {{-- * New lesson form - submits via AJAX (see teacher-dashboard.js) --}}
    <x-card class="mb-6">
        <div id="lessonFormErrors" class="mb-4 hidden bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm px-3 py-2 rounded"></div>
        <form id="newLessonForm">
            <x-lesson-form :students="$students" />
            <div class="mt-4 sm:mt-6">
                <button type="submit" class="btn-primary w-full sm:w-auto">+ Add Class</button>
            </div>
        </form>
    </x-card>

    {{-- * Lesson list with edit/delete functionality --}}
    <x-card :title="'📚 Lessons (' . $stats['total'] . ')'">
        @if($lessons->count() > 0)
            <div class="space-y-2">
                @foreach($lessons as $lesson)
                    <x-lesson-card :lesson="$lesson" :showStudent="true" :showDelete="true" />
                @endforeach
            </div>
        @else
            <x-empty-state message="No lessons this month" />
        @endif
    </x-card>

</div>
@endsection
