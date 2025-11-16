<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $teacher->name }}'s Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-6">
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
            <div class="text-center text-gray-600 mt-2">
                {{ $stats['total'] }} lessons: 
                {{ $stats['completed'] }} completed, 
                {{ $stats['student_absent'] }} absent, 
                {{ $stats['teacher_cancelled'] }} cancelled
            </div>
        </div>

        <!-- Add New Lesson Section -->
        <div class="bg-white rounded-lg shadow mb-4 p-4" x-data="{ showForm: false }">
            <button @click="showForm = !showForm" class="w-full text-left font-semibold text-blue-600 hover:text-blue-800">
                <span x-show="!showForm">+ Add New Lesson</span>
                <span x-show="showForm">− Hide Form</span>
            </button>
            
            <div x-show="showForm" class="mt-4 border-t pt-4" x-cloak>
                <form id="newLessonForm">
                    <x-lesson-form />
                    
                    <div class="flex gap-2 mt-4">
                        <button type="submit" class="px-5 py-2 text-sm font-medium bg-blue-600 text-white rounded hover:bg-blue-700 transition">Save Lesson</button>
                        <button type="button" @click="showForm = false" class="px-5 py-2 text-sm font-medium bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lessons by Week -->
        @foreach($lessonsByWeek as $weekStart => $lessons)
            @php
                $weekStartDate = \Carbon\Carbon::parse($weekStart);
                $weekEndDate = $weekStartDate->copy()->endOfWeek();
                $isCurrentWeek = now()->between($weekStartDate, $weekEndDate);
            @endphp
            
            <div class="bg-white rounded-lg shadow mb-4" x-data="{ open: {{ $isCurrentWeek ? 'true' : 'false' }} }">
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
                                <div class="flex justify-between items-start">
                                    <div class="flex-1 cursor-pointer" @click="editing = true">
                                        <div class="font-medium">
                                            {{ $lesson->class_date->format('D M d') }} - {{ $lesson->student->name }}
                                        </div>
                                        @if($lesson->status === 'completed')
                                            <div class="text-sm text-gray-600 mt-1">
                                                <div><strong>Topic:</strong> {{ $lesson->topic }}</div>
                                                @if($lesson->homework)
                                                    <div><strong>HW:</strong> {{ $lesson->homework }}</div>
                                                @endif
                                                @if($lesson->comments)
                                                    <div><strong>Notes:</strong> {{ $lesson->comments }}</div>
                                                @endif
                                            </div>
                                        @elseif($lesson->status === 'student_absent')
                                            <div class="text-sm text-red-600">
                                                ⚠ Student Absent
                                                @if($lesson->comments)
                                                    <div class="text-xs mt-1">{{ $lesson->comments }}</div>
                                                @endif
                                            </div>
                                        @elseif($lesson->status === 'teacher_cancelled')
                                            <div class="text-sm text-orange-600">
                                                🚫 Teacher Cancelled
                                                @if($lesson->comments)
                                                    <div class="text-xs mt-1">{{ $lesson->comments }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                
                                        <button @click="editing = true" class="text-sm text-blue-600 hover:underline">Edit</button>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <div x-show="editing" class="space-y-2" x-cloak>
                                <form @submit.prevent="saveLesson({{ $lesson->id }})">
                                    <x-lesson-form :lesson="$lesson" />
                                    
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
            <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
                No lessons this month
            </div>
        @endif
    </div>

    <!-- Alpine.js for simple interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        // Create new lesson - use event delegation
        document.addEventListener('submit', function(e) {
            if (e.target.id === 'newLessonForm') {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData.entries());
                
                console.log('Creating lesson:', data);
                
                fetch('/teacher/lesson/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data);
                    if (data.success) {
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

        // Save edited lesson
        function saveLesson(lessonId) {
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            console.log('Updating lesson:', lessonId, data);
            
            fetch('/lesson/' + lessonId + '/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating lesson: ' + (data.message || 'Please check all required fields'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating lesson. Please try again.');
            });
        }

        // Delete lesson
        function deleteLesson(lessonId) {
            if (!confirm('Are you sure you want to delete this lesson? This cannot be undone.')) {
                return;
            }
            
            fetch('/lesson/' + lessonId + '/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>