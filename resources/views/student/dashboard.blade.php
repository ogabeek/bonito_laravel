@extends('layouts.app', ['favicon' => 'favicon-student.svg'])

@section('title', $student->name . "'s Lessons")

@section('content')
@php
    $compactSectionGap = 'mb-6 sm:mb-7';
    $sectionGap = 'mb-7 sm:mb-9';
    $canEditResources = session('teacher_id') || session('admin_authenticated');
    $hasTeacherResources = $canEditResources || filled($student->teacher_notes);
    $hasMaterials = filled($student->materials_url);
    $lessonsTopMargin = $hasMaterials ? 'mt-0' : 'mt-8 sm:mt-10';
@endphp

<div class="p-3 sm:p-6 max-w-4xl mx-auto flex flex-col">
    <div class="order-[10]">
        <x-page-header
            :title="$student->name"
            :subtitle="$student->goal ? 'Goal: ' . $student->goal : null"
        />
    </div>

    @if(config('banners.student_welcome.enabled'))
        <div class="order-[60] sm:order-[20]">
            <x-info-banner :type="config('banners.student_welcome.type')" :icon="false" id="student_welcome" class="{{ $compactSectionGap }}">
                <div class="font-medium mb-1">{{ config('banners.student_welcome.title') }}</div>
                <div class="text-xs opacity-90">{{ config('banners.student_welcome.message') }}</div>
            </x-info-banner>
        </div>
    @endif

    @if(config('banners.student_info.enabled'))
        <div class="order-[60] sm:order-[30]">
            <x-info-banner :type="config('banners.student_info.type')" id="student_info" class="{{ $compactSectionGap }}">
                {{ config('banners.student_info.message') }}
            </x-info-banner>
        </div>
    @endif

    <div class="order-[20] sm:order-[40] {{ $compactSectionGap }}">
        <livewire:student-teacher-info :student="$student" />
    </div>

    @if($hasTeacherResources)
        <div class="order-[30] sm:order-[50] {{ $sectionGap }}">
            @if($canEditResources)
                <livewire:student-teacher-resources :student="$student" />
            @else
                <div class="rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700">
                    <div class="border-b border-gray-200 px-4 py-2 text-xs font-medium text-gray-500">From teacher</div>
                    <div class="whitespace-pre-wrap px-4 py-3 [&_a]:text-gray-600 [&_a]:underline [&_a]:decoration-gray-300 [&_a]:underline-offset-2 hover:[&_a]:text-gray-800">{!! Str::linkify($student->teacher_notes) !!}</div>
                </div>
            @endif
        </div>
    @endif

    @if($availableYears->count() > 1)
        <div class="order-[40] sm:order-[60] mb-3 flex justify-end">
            <div class="inline-flex rounded-md border border-gray-200 bg-white p-0.5 text-xs">
                @foreach($availableYears as $year)
                    <a href="{{ route('student.dashboard', ['student' => $student, 'year' => $year]) }}"
                       class="{{ $year == $selectedYear ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:text-gray-800' }} rounded-sm px-2.5 py-1 font-medium transition-colors">
                        {{ $year }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="order-[50] sm:order-[70] {{ $sectionGap }}">
        <x-weekly-lessons-chart
            :distribution="$weeklyDistribution"
            :stats="$stats"
        />
    </div>

    @if($hasMaterials)
        <div class="order-[65] sm:order-[75] mb-3 sm:mb-4">
            <a href="{{ $student->materials_url }}" target="_blank" rel="noopener" class="group flex min-h-11 w-full items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 shadow-sm shadow-blue-950/5 transition-colors hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-200">
                <span class="flex min-w-0 items-center gap-2">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white/80 text-blue-600 ring-1 ring-blue-100">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M3.5 4A1.5 1.5 0 0 0 2 5.5v9A1.5 1.5 0 0 0 3.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-7A1.5 1.5 0 0 0 16.5 6H10L8.6 4.6A2 2 0 0 0 7.17 4H3.5Z" />
                        </svg>
                    </span>
                    <span class="truncate">Class materials</span>
                </span>
                <span class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-blue-600">
                    Open
                    <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.22 14.78a.75.75 0 0 1 0-1.06L12.94 6H8.75a.75.75 0 0 1 0-1.5h6a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-1.5 0V7.06l-7.72 7.72a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" />
                    </svg>
                </span>
            </a>
        </div>
    @endif

    <x-card title="Lessons" class="order-[70] sm:order-[80] {{ $lessonsTopMargin }}">
        @if($lessonsByMonth->isNotEmpty())
            <div class="space-y-4">
                @foreach($lessonsByMonth as $month => $lessons)
                    @php
                        $monthName = \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F');
                    @endphp
                    <div>
                        <div class="text-xs sm:text-sm font-semibold text-gray-500 mb-2">{{ $monthName }}</div>
                        <div class="space-y-2.5">
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
