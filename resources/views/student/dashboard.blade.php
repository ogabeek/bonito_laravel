@extends('layouts.app')

@section('title', $student->name . "'s Lessons")

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold">{{ $student->name }}</h1>
            @if($student->parent_name)
                <p class="text-gray-600 text-sm">Parent: {{ $student->parent_name }}</p>
            @endif
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
                <div class="p-6 space-y-3">
                    @foreach($pastLessons as $lesson)
                        <div class="border-l-4 pl-4 py-3
                            @if($lesson->status === 'completed') border-green-500 bg-green-50
                            @elseif($lesson->status === 'student_absent') border-red-500 bg-red-50
                            @elseif($lesson->status === 'teacher_cancelled') border-orange-500 bg-orange-50
                            @endif
                        ">
                            <div class="flex justify-between items-start gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="font-semibold text-gray-900">
                                            {{ $lesson->class_date->format('M d, Y') }}
                                        </div>
                                        <x-status-badge :status="$lesson->status" />
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Teacher: {{ $lesson->teacher->name }}
                                    </div>
                                    
                                    @if($lesson->status === 'completed')
                                        <div class="mt-2 text-sm space-y-1">
                                            <div><span class="font-semibold text-gray-700">Topic:</span> {{ $lesson->topic }}</div>
                                            @if($lesson->homework)
                                                <div><span class="font-semibold text-gray-700">Homework:</span> {{ $lesson->homework }}</div>
                                            @endif
                                            @if($lesson->comments)
                                                <div><span class="font-semibold text-gray-700">Notes:</span> {{ $lesson->comments }}</div>
                                            @endif
                                        </div>
                                    @elseif($lesson->status === 'student_absent')
                                        <div class="mt-2 text-sm text-gray-600">
                                            <span class="italic">You were absent</span>
                                            @if($lesson->comments)
                                                <div class="mt-1"><span class="font-semibold">Notes:</span> {{ $lesson->comments }}</div>
                                            @endif
                                        </div>
                                    @elseif($lesson->status === 'teacher_cancelled')
                                        <div class="mt-2 text-sm text-gray-600">
                                            <span class="italic">Lesson was cancelled</span>
                                            @if($lesson->comments)
                                                <div class="mt-1"><span class="font-semibold">Reason:</span> {{ $lesson->comments }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
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
