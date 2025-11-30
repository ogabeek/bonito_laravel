@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Admin Dashboard')

@section('content')
<div class="p-6 max-w-7xl mx-auto" x-data="{ activeTab: 'calendar', showAddTeacher: false, showAddStudent: false, selectedTeacher: '', selectedStatus: '' }">
    
    <x-page-header title="Admin Dashboard" :logoutRoute="route('admin.logout')">
        <a href="{{ route('admin.logs') }}" class="text-sm text-blue-600 hover:underline">Activity Logs</a>
    </x-page-header>


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
                <div class="flex justify-between items-start gap-4 mb-4">
                    <div class="flex items-center gap-4">
                        <h2 class="text-xl font-semibold">{{ $currentMonth->format('F Y') }}</h2>
                        <x-month-nav 
                            :currentMonth="$currentMonth" 
                            :prevMonth="$prevMonth" 
                            :nextMonth="$nextMonth" 
                            routeName="admin.dashboard" 
                            :routeParams="['billing' => $billing ? 1 : null]"
                        />
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.dashboard', ['year' => $currentMonth->year, 'month' => $currentMonth->month]) }}" class="px-3 py-1 text-xs rounded {{ $billing ? 'text-gray-600 bg-gray-100' : 'bg-blue-100 text-blue-700' }}">Calendar</a>
                            <a href="{{ route('admin.dashboard', ['year' => $currentMonth->year, 'month' => $currentMonth->month, 'billing' => 1]) }}" class="px-3 py-1 text-xs rounded {{ $billing ? 'bg-blue-100 text-blue-700' : 'text-gray-600 bg-gray-100' }}">26-25</a>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="grid grid-cols-5 gap-3 text-xs text-gray-600 bg-gray-50 rounded px-3 py-2">
                            <div class="text-center">
                                <div class="font-semibold" style="color: var(--color-status-completed);">{{ $periodStats['completed'] }}</div>
                                <div>Done</div>
                            </div>
                            <div class="text-center">
                                <div class="font-semibold" style="color: var(--color-status-student-cancelled);">{{ $periodStats['student_cancelled'] }}</div>
                                <div>C</div>
                            </div>
                            <div class="text-center">
                                <div class="font-semibold" style="color: var(--color-status-cancelled);">{{ $periodStats['teacher_cancelled'] }}</div>
                                <div>CT</div>
                            </div>
                            <div class="text-center">
                                <div class="font-semibold" style="color: var(--color-status-absent);">{{ $periodStats['student_absent'] }}</div>
                                <div>A</div>
                            </div>
                            <div class="text-center">
                                <div class="font-semibold text-gray-900">{{ $periodStats['total'] }}</div>
                                <div>Total</div>
                            </div>
                        </div>
                        <button @click="showAddStudent = !showAddStudent" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <span x-text="showAddStudent ? 'Cancel' : '+ Add Student'"></span>
                        </button>
                    </div>
                </div>

                <!-- Add Student Form -->
                <div x-show="showAddStudent" x-cloak class="bg-gray-50 rounded-lg p-4 mb-4">
                    <form method="POST" action="{{ route('admin.students.store') }}">
                        @csrf
                        <x-student-form mode="create" />
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 mt-3">Create Student</button>
                    </form>
                </div>

                <!-- Filters -->
                <div class="mb-4 flex gap-3">
                    <select x-model="selectedTeacher" class="pl-3 pr-8 py-2 border rounded">
                        <option value="">All Teachers</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    <select x-model="selectedStatus" class="pl-3 pr-8 py-2 border rounded">
                        <option value="">All Statuses</option>
                        @foreach(\App\Enums\StudentStatus::cases() as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                    <div class="xl:col-span-4 overflow-x-auto border rounded">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-1.5 py-0.5 text-left border-r sticky left-0 bg-gray-50 min-w-[180px]">Student</th>
                                    <th class="px-1 py-0.5 text-right min-w-[44px]"></th>
                                    @for($day = 1; $day <= $daysInMonth; $day++)
                                        @php
                                            $date = $monthStart->copy()->addDays($day - 1);
                                            $isWeekend = $date->isWeekend();
                                            $isToday = $date->isToday();
                                        @endphp
                                        <th class="px-1 py-0.5 text-center min-w-[34px] border-l {{ $isWeekend ? 'bg-gray-100' : '' }} {{ $isToday ? 'bg-blue-50' : '' }}">
                                            <div class="font-semibold text-[11px]">{{ $day }}</div>
                                            <div class="text-[9px] text-gray-500">{{ $date->format('D') }}</div>
                                        </th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    <tr x-show="(selectedTeacher === '' || {{ json_encode($student->teacher_ids) }}.includes(parseInt(selectedTeacher))) && (selectedStatus === '' || selectedStatus === '{{ $student->status->value }}')" class="border-t hover:bg-gray-50">
                                        <td class="px-1.5 py-1 border-r sticky left-0 bg-white">
                                            <div class="flex items-center gap-1 min-w-0">
                                                <x-student-status-dot :status="$student->status" />
                                                <a href="{{ route('admin.students.edit', $student) }}" class="font-medium text-[13px] text-gray-900 hover:text-blue-600 truncate">
                                                    {{ $student->name }}
                                                </a>
                                                <x-student-stats-compact :stats="($studentStats[$student->id] ?? null)" class="w-18 ml-auto text-gray-500" />
                                            </div>
                                            @if($student->teachers->count() > 0)
                                                <div class="text-xs text-gray-500 ml-3.5">{{ $student->teachers->pluck('name')->join(', ') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-1 py-0.5 border-l align-middle">
                                            <x-balance-badge :value="$student->class_balance" class="ml-auto w-full" />
                                        </td>
                                        @for($day = 1; $day <= $daysInMonth; $day++)
                                            @php
                                                $date = $monthStart->copy()->addDays($day - 1);
                                                $dateKey = $student->id . '_' . $date->format('Y-m-d');
                                                $lessons = $lessonsThisMonth->get($dateKey, collect());
                                                $isWeekend = $date->isWeekend();
                                                $isToday = $date->isToday();
                                            @endphp
                                            <td class="px-0.5 py-1 text-center border-l text-[11px] {{ $isWeekend ? 'bg-gray-50' : '' }} {{ $isToday ? 'bg-blue-50' : '' }}">
                                                @foreach($lessons as $lesson)
                                                    <div class="inline-block px-0.75 py-0.25 text-[10px] font-medium rounded"
                                                         style="background: var(--color-status-{{ $lesson->status->cssClass() }}-bg); color: var(--color-status-{{ $lesson->status->cssClass() }});"
                                                         title="{{ $lesson->teacher->name }} - {{ $lesson->status->label() }}">
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
                </div>

                <div class="mt-4">
                    <x-status-legend />
                </div>
            </div>

            <!-- Teachers Tab -->
            <div x-show="activeTab === 'teachers'" x-cloak>
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
                            <th class="px-4 py-2 text-right">Month</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teachers as $teacher)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $teacher->name }}</td>
                                <td class="px-4 py-2">{{ $teacher->students_count }}</td>
                                <td class="px-4 py-2">{{ $teacher->lessons_count }}</td>
                                @php
                                    $ts = $teacherStats[$teacher->id] ?? ['total' => 0, 'completed' => 0, 'student_cancelled' => 0, 'teacher_cancelled' => 0, 'student_absent' => 0];
                                @endphp
                                <td class="px-4 py-2 text-right">
                                    <x-stats-inline :stats="$ts" class="w-20 ml-auto text-gray-500" />
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ route('admin.teachers.delete', $teacher) }}" onsubmit="return confirm('Archive {{ $teacher->name }}? (Can be restored later)')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-orange-600 hover:text-orange-800">Archive</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="flex justify-end items-center mt-4">
                    <button @click="showAddTeacher = !showAddTeacher" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <span x-text="showAddTeacher ? 'Cancel' : '+ Add Teacher'"></span>
                    </button>
                </div>
                                
                <!-- Archived Teachers Section -->
                @if($archivedTeachers->count() > 0)
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-4 text-gray-700">📦 Archived Teachers</h3>
                        <div class="space-y-2">
                            @foreach($archivedTeachers as $teacher)
                                <div class="flex items-center justify-between bg-gray-50 p-3 rounded">
                                    <span class="text-gray-600">{{ $teacher->name }}</span>
                                    <form method="POST" action="{{ route('admin.teachers.restore', $teacher->id) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                            Restore
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-card>
</div>
@endsection
