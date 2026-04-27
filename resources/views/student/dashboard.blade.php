@extends('layouts.app', ['favicon' => 'favicon-student.svg'])

@section('title', $student->name . "'s Lessons")

@section('content')
<div class="p-3 sm:p-6 max-w-4xl mx-auto flex flex-col">
    <div class="order-10">
        <x-page-header
            :title="$student->name"
            :subtitle="$student->goal ? 'Goal: ' . $student->goal : null"
        />
    </div>

    @if(config('banners.student_welcome.enabled'))
        <div class="order-60 sm:order-20">
            <x-info-banner :type="config('banners.student_welcome.type')" :icon="false" id="student_welcome" class="mb-6">
                <div class="font-medium mb-1">{{ config('banners.student_welcome.title') }}</div>
                <div class="text-xs opacity-90">{{ config('banners.student_welcome.message') }}</div>
            </x-info-banner>
        </div>
    @endif

    @if(config('banners.student_info.enabled'))
        <div class="order-60 sm:order-30">
            <x-info-banner :type="config('banners.student_info.type')" id="student_info" class="mb-6">
                {{ config('banners.student_info.message') }}
            </x-info-banner>
        </div>
    @endif

    <div class="order-20 sm:order-40">
        <livewire:student-teacher-info :student="$student" />
    </div>

    <div class="order-30 sm:order-50">
        <livewire:student-teacher-notes :student="$student" />
    </div>

    @if($availableYears->count() > 1)
        <div class="order-40 sm:order-60 flex gap-2 mb-6">
            @foreach($availableYears as $year)
                <a href="{{ route('student.dashboard', ['student' => $student, 'year' => $year]) }}"
                   class="{{ $year == $selectedYear ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} px-3 py-1.5 rounded-full text-sm font-medium transition-colors">
                    {{ $year }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="order-50 sm:order-70">
        <x-weekly-lessons-chart
            :distribution="$weeklyDistribution"
            :stats="$stats"
        />
    </div>

    <x-card title="📚 Lessons" class="order-70 sm:order-80 mt-6">
        @if($lessonsByMonth->isNotEmpty())
            <div class="space-y-4">
                @foreach($lessonsByMonth as $month => $lessons)
                    @php
                        $monthName = \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F');
                    @endphp
                    <div>
                        <div class="text-xs sm:text-sm font-semibold text-gray-500 mb-2">{{ $monthName }}</div>
                        <div class="space-y-2">
                            @foreach($lessons as $lesson)
                                <x-lesson-card :lesson="$lesson" :neutralNonCompleted="true" />
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
