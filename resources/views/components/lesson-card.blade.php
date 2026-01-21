@props([
    'lesson',
    'showTeacher' => false,
    'showStudent' => false,
    'showDelete' => false,
    'dateFormat' => 'D, M d',
])

<div {{ $attributes->merge(['class' => 'lesson-card group border-l-4 pl-3 sm:pl-4 pr-2 py-2 rounded-r flex flex-col sm:flex-row gap-2 sm:gap-4 min-h-[60px] sm:items-center relative']) }}
     style="background: var(--color-status-{{ $lesson->status->cssClass() }}-bg); border-color: var(--color-status-{{ $lesson->status->cssClass() }}-border);">
    
    {{-- Left: Date & Person --}}
    <div class="sm:w-24 sm:shrink-0">
        <div class="font-semibold text-xs sm:text-sm">{{ $lesson->class_date->format($dateFormat) }}</div>
        @if($showStudent)<div class="text-xs text-gray-600">{{ $lesson->student->name }}</div>@endif
        @if($showTeacher)<div class="text-xs text-gray-600">{{ $lesson->teacher->name }}</div>@endif
    </div>
    
    {{-- Right: Details --}}
    <div class="flex-1 text-xs sm:text-sm sm:text-right">
        @if($lesson->status->value === 'completed')
            <div><span class="text-gray-500">Topic:</span> {{ $lesson->topic }}</div>
            @if($lesson->homework)<div class="mt-1"><span class="text-gray-500">HW:</span> {{ $lesson->homework }}</div>@endif
        @else
            <div style="color: var(--color-status-{{ $lesson->status->cssClass() }});">
                @if($lesson->status->value === 'student_absent')
                    ⚠ Student Absent
                @elseif($lesson->status->value === 'student_cancelled')
                    📘 Student Cancelled
                @else
                    🚫 Cancelled
                @endif
            </div>
            @if($lesson->comments)<div class="text-gray-500 text-xs mt-1">{{ $lesson->comments }}</div>@endif
        @endif
    </div>
    
    {{-- Delete (hover only) --}}
    @if($showDelete)
        <button onclick="deleteLesson({{ $lesson->id }})" 
                class="absolute right-1 top-1 w-5 h-5 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                title="Delete">🗑</button>
    @endif
</div>
