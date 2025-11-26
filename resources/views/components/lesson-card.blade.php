@props([
    'lesson',
    'showTeacher' => false,      // Student portal shows teacher
    'showStudent' => false,      // Teacher portal shows student
    'showDelete' => false,       // Only teacher can delete
    'dateFormat' => 'M d, Y',    // Default: "Nov 25, 2025"
    'coloredBg' => true,         // Colored background based on status
    'compact' => false,          // Compact layout for teacher portal
])

@php
    $bgColors = [
        'completed' => $coloredBg ? 'bg-green-50' : '',
        'student_absent' => $coloredBg ? 'bg-red-50' : '',
        'teacher_cancelled' => $coloredBg ? 'bg-orange-50' : '',
    ];
    
    $borderColors = [
        'completed' => 'border-green-500',
        'student_absent' => 'border-red-500',
        'teacher_cancelled' => 'border-orange-500',
    ];
    
    $bgClass = $bgColors[$lesson->status] ?? '';
    $borderClass = $borderColors[$lesson->status] ?? 'border-gray-300';
@endphp

<div class="border-l-4 {{ $borderClass }} pl-4 {{ $compact ? 'py-2' : 'py-3' }} {{ $bgClass }}">
    @if($compact)
            {{-- COMPACT LAYOUT (Teacher Portal) --}}
            <div class="flex justify-between items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="mb-2">
                        <div class="text-sm font-bold text-gray-900">
                            {{ $lesson->class_date->format($dateFormat) }}
                        </div>
                        @if($showStudent)
                            <div class="text-xs font-medium text-gray-700">
                                {{ $lesson->student->name }}
                            </div>
                        @endif
                    </div>
                    @if($showDelete)
                        <button onclick="deleteLesson({{ $lesson->id }})" class="text-xs text-red-600 hover:text-red-800 hover:underline">Delete</button>
                    @endif
                </div>
                
                <div class="flex-1">
                    @if($lesson->status === 'completed')
                        <div class="text-xs text-gray-600 space-y-0.5 flex justify-end">
                            <div class="space-y-0.5">
                                <div class="grid grid-cols-[auto_1fr] gap-2 items-start">
                                    <span class="text-gray-400 text-right">Topic:</span>
                                    <span>{{ $lesson->topic }}</span>
                                </div>
                                @if($lesson->homework)
                                    <div class="grid grid-cols-[auto_1fr] gap-2 items-start">
                                        <span class="text-gray-400 text-right">HW:</span>
                                        <span>{{ $lesson->homework }}</span>
                                    </div>
                                @endif
                                @if($lesson->comments)
                                    <div class="grid grid-cols-[auto_1fr] gap-2 items-start">
                                        <span class="text-gray-400 text-right">Notes:</span>
                                        <span>{{ $lesson->comments }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @elseif($lesson->status === 'student_absent')
                        <div class="text-xs text-red-600 text-right">
                            ⚠ Student Absent
                            @if($lesson->comments)
                                <div class="text-gray-500 mt-0.5">{{ $lesson->comments }}</div>
                            @endif
                        </div>
                    @elseif($lesson->status === 'teacher_cancelled')
                        <div class="text-xs text-orange-600 text-right">
                            🚫 Teacher Cancelled
                            @if($lesson->comments)
                                <div class="text-gray-500 mt-0.5">{{ $lesson->comments }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- STANDARD LAYOUT (Student Portal) --}}
            <div class="flex justify-between items-start gap-4">
                <div class="flex-1">
                    <!-- Header: Date + Status -->
                    <div class="flex items-center gap-2 mb-1">
                        <div class="font-semibold text-gray-900">
                            {{ $lesson->class_date->format($dateFormat) }}
                        </div>
                        <x-status-badge :status="$lesson->status" />
                    </div>
                    
                    <!-- Person (Teacher or Student) -->
                    @if($showTeacher)
                        <div class="text-sm text-gray-600">
                            Teacher: {{ $lesson->teacher->name }}
                        </div>
                    @endif
                    @if($showStudent)
                        <div class="text-sm text-gray-600">
                            Student: {{ $lesson->student->name }}
                        </div>
                    @endif
                    
                    <!-- Lesson Details -->
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
                            <span class="italic">Student was absent</span>
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
                    
                    <!-- Delete Button -->
                    @if($showDelete)
                        <button onclick="deleteLesson({{ $lesson->id }})" class="mt-2 text-xs text-red-600 hover:text-red-800 hover:underline">
                            Delete
                        </button>
                    @endif
                </div>
            </div>
        @endif
</div>
