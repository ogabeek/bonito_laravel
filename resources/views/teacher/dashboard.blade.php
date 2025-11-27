@extends('layouts.app', ['favicon' => 'favicon-teacher.svg'])

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
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">{{ $date->format('F Y') }}</h2>
                <x-month-nav 
                    :currentMonth="$date" 
                    :prevMonth="$prevMonth" 
                    :nextMonth="$nextMonth" 
                    routeName="teacher.dashboard" 
                    :routeParams="['teacher' => $teacher->id]" 
                />
            </div>
            <div class="flex justify-center gap-4 mt-3 border-t pt-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</span>
                    <span class="text-sm text-gray-500">Total</span>
                </div>
                <div class="border-l border-gray-300"></div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold" style="color: var(--color-status-completed);">{{ $stats['completed'] }}</span>
                    <span class="text-sm text-gray-600">Completed</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold" style="color: var(--color-status-absent);">{{ $stats['student_absent'] }}</span>
                    <span class="text-sm text-gray-600">Absent</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-semibold" style="color: var(--color-status-cancelled);">{{ $stats['teacher_cancelled'] }}</span>
                    <span class="text-sm text-gray-600">Cancelled</span>
                </div>
            </div>
        </div>

        <!-- Add Lesson -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <form id="newLessonForm">
                <x-lesson-form :students="$students" />
                
                <div class="mt-6">
                    <button type="submit" class="btn-primary">+ Add Class</button>
                </div>
            </form>
        </div>

        <!-- Lessons History -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold text-gray-700">📚 Lessons ({{ $stats['total'] }})</h2>
            </div>
            
            @if($lessons->count() > 0)
                <div class="p-6 space-y-2">
                    @foreach($lessons as $lesson)
                        <x-lesson-card :lesson="$lesson" :showStudent="true" :showDelete="true" />
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-gray-500">
                    No lessons this month
                </div>
            @endif
        </div>
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

    // Restore form values after page load
    document.addEventListener('DOMContentLoaded', function() {
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