@props([
    'lesson',
    'showTeacher' => false,
    'showStudent' => false,
    'dateFormat' => 'D, M d',
    'neutralNonCompleted' => false,
    'showAbsenceFollowUp' => false,
])

@php
    $statusClass = $lesson->status->cssClass();
    $isCompleted = $lesson->status->value === 'completed';
    $useNeutralStatus = $neutralNonCompleted && ! $isCompleted;
    $classes = 'lesson-card group border-l-4 pl-3 sm:pl-4 pr-3 sm:pr-2 py-2 rounded-r grid grid-cols-1 sm:grid-cols-[6rem_minmax(0,1fr)] gap-x-4 gap-y-1.5 min-h-[74px] sm:min-h-[68px] items-center relative';
    $cardBackground = $useNeutralStatus ? '#f9fafb' : "var(--color-status-{$statusClass}-bg)";
    $cardBorder = $useNeutralStatus ? '#d1d5db' : "var(--color-status-{$statusClass}-border)";
    $cardColor = $useNeutralStatus ? '#6b7280' : "var(--color-status-{$statusClass})";
    $badgeClass = $lesson->status->badgeClass();
    $statusLabel = $lesson->status->displayLabel();
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}
     style="--lesson-card-bg: {{ $cardBackground }}; --lesson-card-border: {{ $cardBorder }}; --lesson-card-color: {{ $cardColor }}; background: var(--lesson-card-bg); border-color: var(--lesson-card-border);">
    
    {{-- Left: Date & Person --}}
    <div class="min-w-0">
        <div class="font-semibold text-xs sm:text-sm text-gray-800">{{ $lesson->class_date->format($dateFormat) }}</div>
        @if($showStudent)<div class="text-xs text-gray-500 truncate">{{ $lesson->student?->name }}</div>@endif
        @if($showTeacher)<div class="text-xs text-gray-500 truncate">{{ $lesson->teacher->name }}</div>@endif
    </div>
    
    {{-- Right: Details --}}
    <div class="min-w-0 text-xs sm:text-sm leading-snug text-gray-700 sm:text-right">
        @if($lesson->status->value === 'completed')
            <div class="[&_a]:break-words"><span class="text-gray-500">Topic:</span> {!! Str::linkify($lesson->topic) !!}</div>
            @if($lesson->homework)<div class="mt-1 [&_a]:break-words"><span class="text-gray-500">HW:</span> {!! Str::linkify($lesson->homework) !!}</div>@endif
        @else
            @if($neutralNonCompleted)
                <div class="flex items-start {{ $lesson->comments ? 'justify-between' : 'justify-end' }} gap-3 sm:justify-end">
                    @if($lesson->comments)
                        <div class="min-w-0 text-left sm:text-right text-gray-500 text-xs leading-snug [&_a]:break-words">{!! Str::linkify($lesson->comments) !!}</div>
                    @endif
                    <span class="{{ $badgeClass }} shrink-0 rounded-full border px-2 py-0.5 text-[11px] font-medium leading-5">
                        {{ $statusLabel }}
                    </span>
                </div>
                @if($showAbsenceFollowUp && $lesson->status === \App\Enums\LessonStatus::STUDENT_ABSENT)
                    <x-absence-follow-up :flags="$lesson->absenceFollowUp()" class="mt-2 justify-end" />
                @endif
            @else
                <div class="lesson-status-text font-medium sm:font-normal" style="color: var(--lesson-card-color);">
                    @if($lesson->status->value === 'student_absent')
                        ⚠ Student Absent
                    @elseif($lesson->status->value === 'student_cancelled')
                        🏃‍➡️ Canceled by student
                    @else
                        🚫 Cancelled
                    @endif
                </div>
                @if($lesson->comments)
                    <div class="text-gray-500 text-xs leading-snug mt-1 [&_a]:break-words">{!! Str::linkify($lesson->comments) !!}</div>
                @endif
                @if($lesson->status === \App\Enums\LessonStatus::STUDENT_ABSENT)
                    <x-absence-follow-up :flags="$lesson->absenceFollowUp()" class="mt-2 justify-end" />
                @endif
            @endif
        @endif
    </div>
    
</div>
