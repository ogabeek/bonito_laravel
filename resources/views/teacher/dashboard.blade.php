@extends('layouts.app')

@section('title', $teacher->name . "'s Dashboard")

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">{{ $teacher->name }}'s Dashboard</h1>
            <form method="POST" action="{{ route('teacher.logout') }}">
                @csrf
                <button class="text-gray-600 hover:text-gray-800">Logout</button>
            </form>
        </div>

        <!-- Month Navigation -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <div class="flex justify-between items-center">
                <a href="?month={{ $date->copy()->subMonth()->format('Y-m') }}" class="text-blue-600 hover:underline">← {{ $date->copy()->subMonth()->format('M') }}</a>
                <h2 class="text-xl font-semibold">{{ $date->format('F Y') }}</h2>
                <a href="?month={{ $date->copy()->addMonth()->format('Y-m') }}" class="text-blue-600 hover:underline">{{ $date->copy()->addMonth()->format('M') }} →</a>
            </div>
            <div class="flex justify-center gap-4 mt-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</span>
                    <span class="text-sm text-gray-500">Total</span>
                </div>
                <div class="border-l border-gray-300"></div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold text-green-600">{{ $stats['completed'] }}</span>
                    <span class="text-sm text-gray-600">Completed</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold text-orange-600">{{ $stats['student_absent'] }}</span>
                    <span class="text-sm text-gray-600">Absent</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold text-red-600">{{ $stats['teacher_cancelled'] }}</span>
                    <span class="text-sm text-gray-600">Cancelled</span>
                </div>
            </div>
        </div>

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
                            <button type="button" @click="showForm = false" class="px-5 py-2 text-sm font-medium bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Cancel</button>
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
                        <x-lesson-card 
                            :lesson="$lesson" 
                            :showStudent="true" 
                            :showDelete="true"
                            :coloredBg="false"
                            :compact="true"
                            dateFormat="D d"
                        />
                    @endforeach
                </div>
            </div>
        @endforeach

        @if($lessonsByWeek->isEmpty())
            <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
                No lessons this month
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Create new lesson - use event delegation
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'newLessonForm') {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            // Remember the selected student and date
            const selectedStudent = data.student_id;
            const selectedDate = data.class_date;
            
            console.log('Creating lesson:', data);
            
            fetch('/teacher/lesson/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                if (data.success) {
                    // Store in sessionStorage (persists during browser session only)
                    sessionStorage.setItem('lastSelectedStudent', selectedStudent);
                    sessionStorage.setItem('lastSelectedDate', selectedDate);
                    sessionStorage.setItem('formInUse', 'true');
                    location.reload();
                } else {
                    alert('Error creating lesson: ' + (data.message || 'Please check all required fields'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating lesson. Please try again.');
            });
        }
    });

    // Restore form values after page load (only during active session)
    document.addEventListener('DOMContentLoaded', function() {
        const formInUse = sessionStorage.getItem('formInUse');
        
        if (formInUse === 'true') {
            const lastStudent = sessionStorage.getItem('lastSelectedStudent');
            const lastDate = sessionStorage.getItem('lastSelectedDate');
            
            // Restore student
            if (lastStudent) {
                const studentSelect = document.querySelector('#newLessonForm select[name="student_id"]');
                if (studentSelect) {
                    studentSelect.value = lastStudent;
                }
            }
            
            // Restore date in Alpine component
            if (lastDate) {
                // Wait for Alpine to initialize
                setTimeout(() => {
                    const calendarContainer = document.querySelector('.calendar-container');
                    if (calendarContainer && calendarContainer._x_dataStack) {
                        const alpineData = calendarContainer._x_dataStack[0];
                        if (alpineData) {
                            alpineData.selected = lastDate;
                        }
                    }
                }, 100);
            }
        }
    });

    // Clear session data when user navigates away or closes form
    window.addEventListener('beforeunload', function() {
        // Only clear if user is navigating away (not reloading after save)
        if (!sessionStorage.getItem('formInUse')) {
            sessionStorage.removeItem('lastSelectedStudent');
            sessionStorage.removeItem('lastSelectedDate');
        }
    });

    // Clear when form is cancelled or hidden
    document.addEventListener('click', function(e) {
        if (e.target.textContent === 'Cancel' || e.target.textContent === '▼ New Lesson') {
            sessionStorage.removeItem('lastSelectedStudent');
            sessionStorage.removeItem('lastSelectedDate');
            sessionStorage.removeItem('formInUse');
        }
    });

    // Delete lesson
    function deleteLesson(lessonId) {
        if (!confirm('Are you sure you want to delete this lesson? This cannot be undone.')) {
            return;
        }
        
        fetch('/lesson/' + lessonId + '/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting lesson');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting lesson. Please try again.');
        });
    }
</script>
@endpush