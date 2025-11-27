@extends('layouts.app')

@section('title', $student->name . "'s Lessons")

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold">{{ $student->name }}</h1>
            @if($student->goal)
                <p class="text-gray-700 mt-2 text-sm"><span class="font-semibold">Goal:</span> {{ $student->goal }}</p>
            @endif
        </div>

        <!-- Upcoming Lessons -->
        @if($upcomingLessons->count() > 0)
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-blue-600">📅 Upcoming Lessons</h2>
                </div>
                <div class="p-6 space-y-3">
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
            </div>
        @endif

        <!-- Past Lessons -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold text-gray-700">📚 Past Lessons ({{ $pastLessons->count() }})</h2>
            </div>
            
            @if($pastLessons->count() > 0)
                <div class="p-6 space-y-2">
                    @foreach($pastLessons as $lesson)
                        <x-lesson-card :lesson="$lesson" :showTeacher="true" />
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-gray-500">
                    <p>No lessons yet</p>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
