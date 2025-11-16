@extends('layouts.app')

@section('title', $teacher->name . "'s Dashboard")

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto">
        
        <!-- Header -->
        <x-dashboard-header :user="$teacher" role="teacher" />

        <!-- Month Navigation with Stats -->
        <x-month-navigation :date="$date" :stats="$stats" />

        <!-- Add New Lesson Section -->
        <div class="bg-white rounded-lg shadow mb-6" x-data="{ showForm: true }">
            <button @click="showForm = !showForm" class="w-full px-6 py-4 text-left font-semibold text-blue-600 hover:text-blue-800 hover:bg-gray-50 flex items-center gap-2">
                <span x-show="!showForm">▶</span>
                <span x-show="showForm">▼</span>
                <span x-show="!showForm">Add New Lesson</span>
                <span x-show="showForm">New Lesson</span>
            </button>
            
            <div x-show="showForm" class="px-6 pb-6" x-cloak>
                <div class="border-t pt-6">
                    <form id="newLessonForm">
                        <x-lesson-form :students="$students" />
                        
                        <div class="flex gap-3 mt-6">
                            <button type="submit" class="btn-primary">Save Lesson</button>
                            <button type="button" @click="showForm = false" data-clear-form-state class="px-5 py-2 text-sm font-medium bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lessons by Week -->
        @foreach($lessonsByWeek as $weekStart => $lessons)
            @php
                $weekStartDate = \Carbon\Carbon::parse($weekStart);
                $weekEndDate = $weekStartDate->copy()->endOfWeek();
                $isCurrentWeek = now()->between($weekStartDate, $weekEndDate);
            @endphp
            
            <div class="bg-white rounded-lg shadow mb-4" x-data="{ open: true }">
                <!-- Week Header -->
                <button 
                    @click="open = !open" 
                    class="w-full px-6 py-4 flex justify-between items-center hover:bg-gray-50"
                >
                    <div>
                        <span class="font-semibold" :class="open ? '' : 'text-gray-600'">
                            <span x-show="open">▼</span>
                            <span x-show="!open">▶</span>
                            {{ $isCurrentWeek ? 'This Week' : 'Week of' }} {{ $weekStartDate->format('M d') }}-{{ $weekEndDate->format('d') }}
                        </span>
                        <span class="text-gray-500 ml-4">{{ $lessons->count() }} lessons</span>
                    </div>
                </button>

                <!-- Week Lessons -->
                <div x-show="open" class="px-6 pb-4 space-y-2">
                    @foreach($lessons as $lesson)
                        <div class="border-l-4 pl-4 py-2 
                            @if($lesson->status === 'completed') border-green-500
                            @elseif($lesson->status === 'student_absent') border-red-500
                            @elseif($lesson->status === 'teacher_cancelled') border-orange-500
                            @else border-blue-500
                            @endif
                        " x-data="{ editing: false }">
                            
                            <!-- Display Mode -->
                            <div x-show="!editing">
                                <x-lesson-display :lesson="$lesson" />
                            </div>

                            <!-- Edit Mode -->
                            <div x-show="editing" class="space-y-2" x-cloak>
                                <form @submit.prevent="saveLesson({{ $lesson->id }}, $event)">
                                    <x-lesson-form :lesson="$lesson" :students="$students" />
                                    
                                    <div class="flex justify-between items-center mt-3">
                                        <div class="flex gap-2">
                                            <button type="submit" class="px-4 py-1.5 text-sm font-medium bg-blue-600 text-white rounded hover:bg-blue-700 transition">Save</button>
                                            <button type="button" @click="editing = false" class="px-4 py-1.5 text-sm font-medium bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Cancel</button>
                                        </div>
                                        <button type="button" onclick="deleteLesson({{ $lesson->id }})" class="px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-800 transition">🗑 Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if($lessonsByWeek->isEmpty())
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                <p>No lessons scheduled for {{ $date->format('F Y') }}</p>
                <p class="text-sm mt-2">Click "Add New Lesson" above to get started!</p>
            </div>
        @endif

    </div>
</div>

<!-- Alpine.js for dropdowns and modals -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection

@push('scripts')
<script src="{{ asset('js/lesson-manager.js') }}"></script>
@endpush
