@props([
    'lesson',
    'showTeacher' => false,      // Student portal shows teacher
    'showStudent' => false,      // Teacher portal shows student
    'showDelete' => false,       // Only teacher can delete
    'dateFormat' => 'M d, Y',    // Default: "Nov 25, 2025"
])

@php
    // Map status to CSS variable names
    $statusMap = [
        'completed' => 'completed',
        'student_absent' => 'absent',
        'teacher_cancelled' => 'cancelled',
    ];
    $cssStatus = $statusMap[$lesson->status] ?? 'completed';
@endphp

<div class="border-l-4 pl-4 py-3 rounded-r" 
     style="background-color: var(--color-status-{{ $cssStatus }}-bg); border-color: var(--color-status-{{ $cssStatus }}-border);">
    
    <div class="flex justify-between items-start gap-4">
        {{-- LEFT: Date, Person, Delete --}}
        <div class="flex-shrink-0">
            <div class="font-semibold text-gray-900">
                {{ $lesson->class_date->format($dateFormat) }}
            </div>
            @if($showStudent)
                <div class="text-sm text-gray-600">{{ $lesson->student->name }}</div>
            @endif
            @if($showTeacher)
                <div class="text-sm text-gray-600">{{ $lesson->teacher->name }}</div>
            @endif
            @if($showDelete)
                <button onclick="deleteLesson({{ $lesson->id }})" class="mt-1 text-xs text-red-600 hover:text-red-800 hover:underline">Delete</button>
            @endif
        </div>
        
        {{-- RIGHT: Status & Details --}}
        <div class="flex-1 text-right">
            @if($lesson->status === 'completed')
                <div class="text-sm text-gray-700 space-y-0.5">
                    <div class="grid grid-cols-[auto_1fr] gap-2 items-start justify-end">
                        <span class="text-gray-500">Topic:</span>
                        <span class="text-left">{{ $lesson->topic }}</span>
                    </div>
                    @if($lesson->homework)
                        <div class="grid grid-cols-[auto_1fr] gap-2 items-start justify-end">
                            <span class="text-gray-500">HW:</span>
                            <span class="text-left">{{ $lesson->homework }}</span>
                        </div>
                    @endif
                    @if($lesson->comments)
                        <div class="grid grid-cols-[auto_1fr] gap-2 items-start justify-end">
                            <span class="text-gray-500">Notes:</span>
                            <span class="text-left">{{ $lesson->comments }}</span>
                        </div>
                    @endif
                </div>
            @elseif($lesson->status === 'student_absent')
                <div class="text-sm" style="color: var(--color-status-absent);">
                    ⚠ Student Absent
                    @if($lesson->comments)
                        <div class="text-gray-600 mt-0.5">{{ $lesson->comments }}</div>
                    @endif
                </div>
            @elseif($lesson->status === 'teacher_cancelled')
                <div class="text-sm" style="color: var(--color-status-cancelled);">
                    🚫 Cancelled
                    @if($lesson->comments)
                        <div class="text-gray-600 mt-0.5">{{ $lesson->comments }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
