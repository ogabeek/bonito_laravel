@props(['lesson', 'balance' => null])

@php
    $status = $lesson->status;
    $css = $status->cssClass();
    $needsRecovery = $lesson->refund_requested;
    $balanceLabel = $balance === null ? null : rtrim(rtrim(number_format((float) $balance, 1, '.', ''), '0'), '.');
@endphp

{{-- Calendar day chip with a logs-style hover card (teleported to <body> so it
     is not clipped by the scrollable calendar). --}}
<span {{ $attributes }}
      class="cal-lesson-chip {{ $needsRecovery ? 'ring-1 ring-amber-500' : '' }}"
      style="background: var(--color-status-{{ $css }}-bg); color: var(--color-status-{{ $css }});"
      x-data="{ show: false, x: 0, y: 0 }"
      @mouseenter="show = true"
      @mousemove="x = $event.clientX; y = $event.clientY"
      @mouseleave="show = false">
    <span>{{ substr($lesson->teacher->name, 0, 1) }}</span>
    @if($balanceLabel !== null)
        <span class="cal-chip-balance" @if($balance < 0) style="color: var(--color-status-absent);" @endif>{{ $balanceLabel }}</span>
    @endif

    <template x-teleport="body">
        <div x-show="show" x-cloak
             :style="`top: ${y + 16}px; left: ${Math.min(x + 16, window.innerWidth - 272)}px`"
             class="fixed z-50 w-64 rounded-lg border bg-white p-3 shadow-lg text-xs text-gray-600 space-y-0.5 pointer-events-none">
            <div class="mb-1 truncate font-semibold text-gray-900">{{ $lesson->teacher->name }}</div>
            @if(filled($lesson->topic))
                <div><span class="font-medium text-gray-700">Topic:</span> {{ $lesson->topic }}</div>
            @endif
            @if(filled($lesson->homework))
                <div><span class="font-medium text-gray-700">Homework:</span> {{ $lesson->homework }}</div>
            @endif
            @if(filled($lesson->comments))
                <div><span class="font-medium text-gray-700">Notes:</span> {{ $lesson->comments }}</div>
            @endif
            @if($status === \App\Enums\LessonStatus::STUDENT_ABSENT)
                <x-absence-follow-up :flags="$lesson->absenceFollowUp()" class="pt-2" />
            @endif
        </div>
    </template>
</span>
