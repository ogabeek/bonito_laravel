@extends('layouts.app', ['favicon' => 'favicon-teacher.svg'])

@section('title', $teacher->name . "'s Dashboard")

@section('content')
<div class="p-6 max-w-5xl mx-auto">
    
    <x-page-header 
        :title="$teacher->name . \"'s Dashboard\"" 
        :logoutRoute="route('teacher.logout')" 
    />

    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc ml-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card class="mb-6">
        <div class="flex justify-between items-start gap-4 mb-3">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold">{{ $date->format('F Y') }}</h2>
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

    <x-card class="mb-6">
        <div id="lessonFormErrors" class="mb-4 hidden bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2 rounded"></div>
        <form id="newLessonForm">
            <x-lesson-form :students="$students" />
            <div class="mt-6">
                <button type="submit" class="btn-primary">+ Add Class</button>
            </div>
        </form>
    </x-card>

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
