@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="p-6" x-data="adminDashboard()">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">Admin Dashboard</h1>
                <p class="text-gray-600 text-sm">Manage everything in one place</p>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="text-gray-600 hover:text-gray-800">Logout</button>
            </form>
        </div>

        <!-- Stats Bar -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Teachers</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['teachers'] }}</p>
                    </div>
                    <span class="text-3xl">👨‍🏫</span>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Students</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['students'] }}</p>
                    </div>
                    <span class="text-3xl">👨‍🎓</span>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Lessons - {{ $currentMonth->format('M Y') }}</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['lessons_this_month'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-t-lg shadow-sm border-b">
            <div class="flex gap-1 px-4">
                <button @click="activeTab = 'calendar'" 
                        :class="activeTab === 'calendar' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-3 font-medium transition">
                    📅 Calendar
                </button>
                <button @click="activeTab = 'teachers'" 
                        :class="activeTab === 'teachers' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-3 font-medium transition">
                    👨‍🏫 Teachers
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="bg-white rounded-b-lg shadow-lg p-6">

            <!-- CALENDAR TAB -->
            <div x-show="activeTab === 'calendar'" x-cloak>
                <div class="mb-4 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div>
                            <h2 class="text-2xl font-bold">Monthly Calendar</h2>
                            <p class="text-gray-600 text-sm">{{ $currentMonth->format('F Y') }}</p>
                        </div>
                        
                        <!-- Month Navigation -->
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.dashboard', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" 
                               class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium transition"
                               title="Previous month">
                                ← {{ $prevMonth->format('M') }}
                            </a>
                            
                            @if(!$currentMonth->isCurrentMonth())
                                <a href="{{ route('admin.dashboard') }}" 
                                   class="px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-sm font-medium transition"
                                   title="Go to current month">
                                    Today
                                </a>
                            @endif
                            
                            <a href="{{ route('admin.dashboard', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" 
                               class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium transition"
                               title="Next month">
                                {{ $nextMonth->format('M') }} →
                            </a>
                        </div>
                    </div>
                    
                    <button @click="showAddStudent = !showAddStudent" class="btn-primary">
                        <span x-show="!showAddStudent">+ Add Student</span>
                        <span x-show="showAddStudent">✕ Cancel</span>
                    </button>
                </div>

                <!-- Add Student Form -->
                <div x-show="showAddStudent" x-cloak class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <h3 class="font-semibold mb-3">Create New Student</h3>
                    <form method="POST" action="{{ route('admin.students.store') }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Student Name *</label>
                                <input type="text" name="name" required class="form-input w-full">
                            </div>
                            <div>
                                <label class="form-label">Parent Name</label>
                                <input type="text" name="parent_name" class="form-input w-full">
                            </div>
                            <div>
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input w-full">
                            </div>
                            <div>
                                <label class="form-label">Goal</label>
                                <input type="text" name="goal" class="form-input w-full">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="2" class="form-input w-full"></textarea>
                        </div>
                        <button type="submit" class="btn-primary">Create Student</button>
                    </form>
                </div>

                <!-- Filter by Teacher -->
                <div class="mb-4 flex gap-4 items-center">
                    <label class="text-sm font-medium">Filter by Teacher:</label>
                    <select x-model="selectedTeacher" class="form-input text-sm">
                        <option value="">All Teachers</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Calendar Grid -->
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-700 border-r bg-gray-100 sticky left-0 z-10 min-w-[150px]">
                                    Student
                                </th>
                                @for($day = 1; $day <= $daysInMonth; $day++)
                                    @php
                                        $date = $monthStart->copy()->addDays($day - 1);
                                        $isWeekend = $date->isWeekend();
                                        $isToday = $date->isToday();
                                    @endphp
                                    <th class="px-2 py-2 text-center min-w-[50px] border-l {{ $isWeekend ? 'bg-gray-200' : '' }} {{ $isToday ? 'bg-blue-100' : '' }}">
                                        <div class="font-bold">{{ $day }}</div>
                                        <div class="text-xs text-gray-500">{{ $date->format('D') }}</div>
                                    </th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                @php
                                    // Filter by selected teacher if applicable
                                    $studentTeacherIds = $student->teachers->pluck('id')->toArray();
                                @endphp
                                <tr x-show="selectedTeacher === '' || {{ json_encode($studentTeacherIds) }}.includes(parseInt(selectedTeacher))" 
                                    class="hover:bg-gray-50/50 border-t group">
                                    <td class="px-3 py-3 border-r bg-white/95 sticky left-0 z-10 backdrop-blur-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <!-- Student Name & Link -->
                                            <div class="flex-1 min-w-0">
                                                <a href="{{ route('admin.students.edit', $student) }}" 
                                                   class="font-medium text-gray-900 hover:text-blue-600 transition-colors block truncate">
                                                    {{ $student->name }}
                                                </a>
                                                
                                                @php
                                                    $lessonStats = $studentLessonStats->get($student->id, ['total' => 0, 'completed' => 0]);
                                                @endphp
                                                
                                                <!-- Teachers (compact, single line) -->
                                                @if($student->teachers->count() > 0)
                                                    <div class="text-xs text-gray-500 truncate mt-0.5">
                                                        {{ $student->teachers->pluck('name')->join(', ') }}
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <!-- Actions & Stats (compact icons on right) -->
                                            <div class="flex items-center gap-2 text-xs shrink-0">
                                                @if($lessonStats['total'] > 0)
                                                    <span class="text-gray-600 font-medium" title="{{ $lessonStats['completed'] }} completed / {{ $lessonStats['total'] }} total">
                                                        {{ $lessonStats['completed'] }}/{{ $lessonStats['total'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    @for($day = 1; $day <= $daysInMonth; $day++)
                                        @php
                                            $date = $monthStart->copy()->addDays($day - 1);
                                            $dateKey = $student->id . '_' . $date->format('Y-m-d');
                                            $lessons = $lessonsThisMonth->get($dateKey, collect());
                                            $isWeekend = $date->isWeekend();
                                            $isToday = $date->isToday();
                                        @endphp
                                        <td class="px-1 py-2 text-center border-l {{ $isWeekend ? 'bg-gray-50' : '' }} {{ $isToday ? 'bg-blue-50 ring-1 ring-blue-200 ring-inset' : '' }}">
                                            @if($lessons->isNotEmpty())
                                                <div class="flex flex-col gap-1 items-center">
                                                    @foreach($lessons as $lesson)
                                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold transition-transform hover:scale-110 cursor-default
                                                            {{ $lesson->status === 'completed' ? 'bg-green-500 text-white shadow-sm' : '' }}
                                                            {{ $lesson->status === 'student_absent' ? 'bg-yellow-400 text-white shadow-sm' : '' }}
                                                            {{ $lesson->status === 'teacher_cancelled' ? 'bg-red-500 text-white shadow-sm' : '' }}"
                                                            title="{{ $lesson->teacher->name }} - {{ ucfirst(str_replace('_', ' ', $lesson->status)) }}">
                                                            {{ substr($lesson->teacher->name, 0, 1) }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Legend -->
                <div class="mt-4 flex gap-6 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-green-500 rounded"></div>
                        <span>Completed</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-yellow-500 rounded"></div>
                        <span>Student Absent</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-red-500 rounded"></div>
                        <span>Teacher Cancelled</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-blue-100 border border-blue-300 rounded"></div>
                        <span>Today</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-gray-200 rounded"></div>
                        <span>Weekend</span>
                    </div>
                </div>
            </div>

            <!-- TEACHERS TAB -->
            <div x-show="activeTab === 'teachers'" x-cloak>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">Teachers Management</h2>
                    <button @click="showAddTeacher = !showAddTeacher" class="btn-primary">
                        <span x-show="!showAddTeacher">+ Add Teacher</span>
                        <span x-show="showAddTeacher">✕ Cancel</span>
                    </button>
                </div>

                <!-- Add Teacher Form -->
                <div x-show="showAddTeacher" x-cloak class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h3 class="font-semibold mb-3">Create New Teacher</h3>
                    <form method="POST" action="{{ route('admin.teachers.create') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @csrf
                        <div>
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" required class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Password *</label>
                            <input type="text" name="password" required class="form-input w-full" placeholder="Min 4 chars">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="btn-primary w-full">Create Teacher</button>
                        </div>
                    </form>
                </div>

                <!-- Teachers Table -->
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lessons</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($teachers as $teacher)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $teacher->name }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $teacher->id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $teacher->students_count }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $teacher->lessons_count }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.teachers.delete', $teacher) }}" onsubmit="return confirm('Delete {{ $teacher->name }}?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function adminDashboard() {
    return {
        activeTab: 'calendar',
        showAddTeacher: false,
        showAddStudent: false,
        selectedTeacher: ''
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
