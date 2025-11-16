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
                {{ $stats['teacher_cancelled'] }} cancelled,
                {{ $stats['scheduled'] }} scheduled
            </div>
        </div>

        <!-- Add New Lesson Section -->
        <div class="bg-white rounded-lg shadow mb-4 p-4" x-data="{ showForm: false }">
            <button @click="showForm = !showForm" class="w-full text-left font-semibold text-blue-600 hover:text-blue-800">
                <span x-show="!showForm">+ Add New Lesson</span>
                <span x-show="showForm">− Hide Form</span>
            </button>
            
            <div x-show="showForm" class="mt-4 border-t pt-4" x-cloak>
                <form id="newLessonForm" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="class_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                        <select name="student_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Select Student</option>
                            @php
                                $allStudents = \App\Models\Student::orderBy('name')->get();
                            @endphp
                            @foreach($allStudents as $student)
                                <option value="{{ $student->id }}">{{ $student->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="newLessonStatus" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="student_absent">Student Absent</option>
                            <option value="teacher_cancelled">Teacher Cancelled</option>
                        </select>
                    </div>
                    
                    <div id="newLessonDetails" style="display: none;">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Topic *</label>
                            <input type="text" name="topic" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Homework</label>
                            <textarea name="homework" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Comments</label>
                            <textarea name="comments" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Lesson</button>
                        <button type="button" @click="showForm = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
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
                                            <div class="text-sm text-red-600">⚠ Student Absent</div>
                                        @elseif($lesson->status === 'teacher_cancelled')
                                            <div class="text-sm text-orange-600">🚫 Teacher Cancelled</div>
                                        @else
                                            <div class="text-sm text-blue-600">📅 Scheduled</div>
                                        @endif
                                    </div>
                                
                                        <button @click="editing = true" class="text-sm text-blue-600 hover:underline">Edit</button>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <div x-show="editing" class="space-y-2" x-cloak>
                                <form @submit.prevent="saveLesson({{ $lesson->id }})" class="space-y-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                        <select name="status" id="status_{{ $lesson->id }}" class="w-full px-2 py-1 text-sm border border-gray-300 rounded">
                                            <option value="scheduled" {{ $lesson->status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                            <option value="completed" {{ $lesson->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="student_absent" {{ $lesson->status === 'student_absent' ? 'selected' : '' }}>Student Absent</option>
                                            <option value="teacher_cancelled" {{ $lesson->status === 'teacher_cancelled' ? 'selected' : '' }}>Teacher Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <div id="details_{{ $lesson->id }}" style="{{ in_array($lesson->status, ['completed']) ? '' : 'display:none' }}">
                                        <div class="mb-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Topic *</label>
                                            <input type="text" name="topic" value="{{ $lesson->topic }}" class="w-full px-2 py-1 text-sm border border-gray-300 rounded">
                                        </div>
                                        
                                        <div class="mb-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Homework</label>
                                            <textarea name="homework" rows="2" class="w-full px-2 py-1 text-sm border border-gray-300 rounded">{{ $lesson->homework }}</textarea>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Comments</label>
                                            <textarea name="comments" rows="2" class="w-full px-2 py-1 text-sm border border-gray-300 rounded">{{ $lesson->comments }}</textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-2">
                                        <button type="submit" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                                        <button type="button" @click="editing = false" class="px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <script>
                            // Show/hide details based on status for lesson {{ $lesson->id }}
                            document.addEventListener('DOMContentLoaded', function() {
                                const statusSelect = document.getElementById('status_{{ $lesson->id }}');
                                const detailsDiv = document.getElementById('details_{{ $lesson->id }}');
                                
                                if (statusSelect) {
                                    statusSelect.addEventListener('change', function() {
                                        detailsDiv.style.display = this.value === 'completed' ? 'block' : 'none';
                                    });
                                }
                            });
                        </script>
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
        // Show/hide topic fields based on status for new lesson form
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('newLessonStatus');
            const detailsDiv = document.getElementById('newLessonDetails');
            
            statusSelect.addEventListener('change', function() {
                detailsDiv.style.display = this.value === 'completed' ? 'block' : 'none';
            });
        });

        // Create new lesson
        document.getElementById('newLessonForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
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
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error creating lesson. Make sure all required fields are filled.');
                }
            });
        });

        // Save edited lesson
        function saveLesson(lessonId) {
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
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
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: Topic is required for completed lessons');
                }
            });
        }
    </script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>