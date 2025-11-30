@extends('layouts.app', ['favicon' => 'favicon-teacher.svg'])

@section('title', $teacher->name . "'s Dashboard")

@section('content')
<div class="p-6 max-w-5xl mx-auto">
    
    <x-page-header 
        :title="$teacher->name . \"'s Dashboard\"" 
        :logoutRoute="route('teacher.logout')" 
    />

    <x-card class="mb-6">
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

@push('scripts')
<script>
    const lessonErrorBox = document.getElementById('lessonFormErrors');
    const clearLessonErrors = () => {
        if (!lessonErrorBox) return;
        lessonErrorBox.innerHTML = '';
        lessonErrorBox.classList.add('hidden');
    };
    const showLessonErrors = (messages) => {
        if (!lessonErrorBox) return;
        const list = Array.isArray(messages) ? messages : [messages];
        lessonErrorBox.innerHTML = '<ul class="list-disc pl-4">' + list.map(m => `<li>${m}</li>`).join('') + '</ul>';
        lessonErrorBox.classList.remove('hidden');
    };

    // Create new lesson - use event delegation
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'newLessonForm') {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            clearLessonErrors();
            
            // Remember the selected student and date
            const selectedStudent = data.student_id;
            const selectedDate = data.class_date;

            fetch('/teacher/lesson/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Store in sessionStorage (persists during browser session only)
                    sessionStorage.setItem('lastSelectedStudent', selectedStudent);
                    sessionStorage.setItem('lastSelectedDate', selectedDate);
                    location.reload();
                } else if (data.errors) {
                    showLessonErrors(Object.values(data.errors).flat());
                } else {
                    showLessonErrors(data.message || 'Please check all required fields');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showLessonErrors('Error creating lesson. Please try again.');
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

        clearLessonErrors();
        
        fetch('/lesson/' + lessonId + '/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else if (data.errors) {
                showLessonErrors(Object.values(data.errors).flat());
            } else {
                showLessonErrors(data.message || 'Error deleting lesson');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showLessonErrors('Error deleting lesson. Please try again.');
        });
    }
</script>
@endpush
