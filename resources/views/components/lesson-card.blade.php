@props([
    'lesson',
    'showTeacher' => false,
    'showStudent' => false,
    'showDelete' => false,
    'dateFormat' => 'D, M d',
    'mutedOnMobile' => false,
])

@php
    $statusClass = $lesson->status->cssClass();
    $classes = 'lesson-card group border-l-4 pl-3 sm:pl-4 pr-3 sm:pr-2 py-2 rounded-r grid grid-cols-1 sm:grid-cols-[6rem_minmax(0,1fr)] gap-x-4 gap-y-1.5 min-h-[74px] sm:min-h-[68px] items-center relative';

    if ($mutedOnMobile) {
        $classes .= ' lesson-card-muted-mobile';
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}
     style="--lesson-card-bg: var(--color-status-{{ $statusClass }}-bg); --lesson-card-border: var(--color-status-{{ $statusClass }}-border); --lesson-card-color: var(--color-status-{{ $statusClass }}); background: var(--lesson-card-bg); border-color: var(--lesson-card-border);">
    
    {{-- Left: Date & Person --}}
    <div class="min-w-0">
        <div class="font-semibold text-xs sm:text-sm">{{ $lesson->class_date->format($dateFormat) }}</div>
        @if($showStudent)<div class="text-xs text-gray-600">{{ $lesson->student->name }}</div>@endif
        @if($showTeacher)<div class="text-xs text-gray-600">{{ $lesson->teacher->name }}</div>@endif
    </div>
    
    {{-- Right: Details --}}
    <div class="min-w-0 text-xs sm:text-sm leading-snug sm:text-right">
        @if($lesson->status->value === 'completed')
            <div><span class="text-gray-500">Topic:</span> {{ $lesson->topic }}</div>
            @if($lesson->homework)<div class="mt-1"><span class="text-gray-500">HW:</span> {{ $lesson->homework }}</div>@endif
        @else
            <div style="color: var(--lesson-card-color);">
                @if($lesson->status->value === 'student_absent')
                    ⚠ Student Absent
                @elseif($lesson->status->value === 'student_cancelled')
                    🏃‍➡️ Student Cancelled
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
