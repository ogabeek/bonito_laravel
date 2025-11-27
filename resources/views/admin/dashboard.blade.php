@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Admin Dashboard')

@section('content')
<div class="p-6 max-w-7xl mx-auto" x-data="{ activeTab: 'calendar', showAddTeacher: false, showAddStudent: false, selectedTeacher: '' }">
    
    <x-page-header title="Admin Dashboard" :logoutRoute="route('admin.logout')" />

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <x-card class="p-4">
            <p class="text-sm text-gray-600">Teachers</p>
            <p class="text-2xl font-bold">{{ $stats['teachers'] }}</p>
        </x-card>
        <x-card class="p-4">
            <p class="text-sm text-gray-600">Students</p>
            <p class="text-2xl font-bold">{{ $stats['students'] }}</p>
        </x-card>
        <x-card class="p-4">
            <p class="text-sm text-gray-600">Lessons - {{ $currentMonth->format('M Y') }}</p>
            <p class="text-2xl font-bold">{{ $stats['lessons_this_month'] }}</p>
        </x-card>
    </div>

    <x-card>
        <div class="border-b flex gap-4 px-4">
            <button @click="activeTab = 'calendar'" 
                    :class="activeTab === 'calendar' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'"
                    class="py-3 font-medium">Calendar</button>
            <button @click="activeTab = 'teachers'" 
                    :class="activeTab === 'teachers' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'"
                    class="py-3 font-medium">Teachers</button>
        </div>

        <div class="p-6">
            <!-- Calendar Tab -->
            <div x-show="activeTab === 'calendar'" x-cloak>
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-4">
                        <h2 class="text-xl font-semibold">{{ $currentMonth->format('F Y') }}</h2>
                        <x-month-nav :currentMonth="$currentMonth" :prevMonth="$prevMonth" :nextMonth="$nextMonth" routeName="admin.dashboard" />
                    </div>
                    <button @click="showAddStudent = !showAddStudent" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <span x-text="showAddStudent ? 'Cancel' : '+ Add Student'"></span>
                    </button>
                </div>

                <!-- Add Student Form -->
                <div x-show="showAddStudent" x-cloak class="bg-gray-50 rounded-lg p-4 mb-4">
                    <form method="POST" action="{{ route('admin.students.store') }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <input type="text" name="name" placeholder="Student Name *" required class="px-3 py-2 border rounded">
                            <input type="text" name="parent_name" placeholder="Parent Name" class="px-3 py-2 border rounded">
                            <input type="email" name="email" placeholder="Email" class="px-3 py-2 border rounded">
                            <input type="text" name="goal" placeholder="Goal" class="px-3 py-2 border rounded">
                        </div>
                        <textarea name="description" placeholder="Description" rows="2" class="w-full px-3 py-2 border rounded"></textarea>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create Student</button>
                    </form>
                </div>

                <!-- Filter -->
                <div class="mb-4">
                    <select x-model="selectedTeacher" class="pl-3 pr-8 py-2 border rounded">
                        <option value="">All Teachers</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Calendar Grid -->
                <div class="overflow-x-auto border rounded">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left border-r sticky left-0 bg-gray-50 min-w-[150px]">Student</th>
                                @for($day = 1; $day <= $daysInMonth; $day++)
                                    @php
                                        $date = $monthStart->copy()->addDays($day - 1);
                                        $isWeekend = $date->isWeekend();
                                        $isToday = $date->isToday();
                                    @endphp
                                    <th class="px-2 py-2 text-center min-w-[50px] border-l {{ $isWeekend ? 'bg-gray-100' : '' }} {{ $isToday ? 'bg-blue-50' : '' }}">
                                        <div class="font-bold">{{ $day }}</div>
                                        <div class="text-xs text-gray-500">{{ $date->format('D') }}</div>
                                    </th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                @php
                                    $studentTeacherIds = $student->teachers->pluck('id')->toArray();
                                @endphp
                                <tr x-show="selectedTeacher === '' || {{ json_encode($studentTeacherIds) }}.includes(parseInt(selectedTeacher))" class="border-t hover:bg-gray-50">
                                    <td class="px-3 py-2 border-r sticky left-0 bg-white">
                                        <a href="{{ route('admin.students.edit', $student) }}" class="font-medium text-gray-900 hover:text-blue-600">
                                            {{ $student->name }}
                                        </a>
                                        @if($student->teachers->count() > 0)
                                            <div class="text-xs text-gray-500">{{ $student->teachers->pluck('name')->join(', ') }}</div>
                                        @endif
                                    </td>
                                    @for($day = 1; $day <= $daysInMonth; $day++)
                                        @php
                                            $date = $monthStart->copy()->addDays($day - 1);
                                            $dateKey = $student->id . '_' . $date->format('Y-m-d');
                                            $lessons = $lessonsThisMonth->get($dateKey, collect());
                                            $isWeekend = $date->isWeekend();
                                            $isToday = $date->isToday();
                                        @endphp
                                        <td class="px-1 py-2 text-center border-l {{ $isWeekend ? 'bg-gray-50' : '' }} {{ $isToday ? 'bg-blue-50' : '' }}">
                                            @foreach($lessons as $lesson)
                                                @php
                                                    $statusClass = ['completed' => 'completed', 'student_absent' => 'absent', 'teacher_cancelled' => 'cancelled'][$lesson->status] ?? 'completed';
                                                @endphp
                                                <div class="inline-block px-1.5 py-0.5 text-xs font-medium rounded" 
                                                     style="background: var(--color-status-{{ $statusClass }}-bg); color: var(--color-status-{{ $statusClass }});"
                                                     title="{{ $lesson->teacher->name }} - {{ ucfirst(str_replace('_', ' ', $lesson->status)) }}">
                                                    {{ substr($lesson->teacher->name, 0, 1) }}
                                                </div>
                                            @endforeach
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <x-status-legend />
                </div>
            </div>

            <!-- Teachers Tab -->
            <div x-show="activeTab === 'teachers'" x-cloak>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Teachers</h2>
                    <button @click="showAddTeacher = !showAddTeacher" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <span x-text="showAddTeacher ? 'Cancel' : '+ Add Teacher'"></span>
                    </button>
                </div>

                <!-- Add Teacher Form -->
                <div x-show="showAddTeacher" x-cloak class="bg-gray-50 rounded-lg p-4 mb-4">
                    <form method="POST" action="{{ route('admin.teachers.create') }}" class="flex gap-4">
                        @csrf
                        <input type="text" name="name" placeholder="Name *" required class="flex-1 px-3 py-2 border rounded">
                        <input type="text" name="password" placeholder="Password *" required class="flex-1 px-3 py-2 border rounded">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create</button>
                    </form>
                </div>

                <!-- Teachers Table -->
                <table class="w-full border rounded">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Teacher</th>
                            <th class="px-4 py-2 text-left">Students</th>
                            <th class="px-4 py-2 text-left">Lessons</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teachers as $teacher)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $teacher->name }}</td>
                                <td class="px-4 py-2">{{ $teacher->students_count }}</td>
                                <td class="px-4 py-2">{{ $teacher->lessons_count }}</td>
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ route('admin.teachers.delete', $teacher) }}" onsubmit="return confirm('Delete {{ $teacher->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-card>
</div>
@endsection
