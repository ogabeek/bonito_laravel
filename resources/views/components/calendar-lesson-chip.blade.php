@props(['lesson'])

@php
    $status = $lesson->status;
    $css = $status->cssClass();
    $refund = $lesson->refund_requested;
@endphp

{{-- Calendar day chip with a logs-style hover card (teleported to <body> so it
     is not clipped by the scrollable calendar). --}}
<span {{ $attributes }}
      class="cal-lesson-chip {{ $refund ? 'ring-1 ring-amber-500' : '' }}"
      style="background: var(--color-status-{{ $css }}-bg); color: var(--color-status-{{ $css }});"
      x-data="{ show: false, x: 0, y: 0 }"
      @mouseenter="show = true"
      @mousemove="x = $event.clientX; y = $event.clientY"
      @mouseleave="show = false">
    {{ substr($lesson->teacher->name, 0, 1) }}

    <template x-teleport="body">
        <div x-show="show" x-cloak
             :style="`top: ${y + 16}px; left: ${Math.min(x + 16, window.innerWidth - 272)}px`"
             class="fixed z-50 w-64 rounded-lg border bg-white p-3 shadow-lg text-xs text-gray-600 space-y-0.5 pointer-events-none">
            <div class="flex items-center justify-between gap-2 mb-1">
                <span class="font-semibold text-gray-900 truncate">{{ $lesson->teacher->name }}</span>
                <span class="flex-shrink-0 px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $status->badgeClass() }}">{{ $status->label() }}</span>
            </div>
            <div><span class="font-medium text-gray-700">Date:</span> {{ $lesson->class_date->format('M d, Y') }}</div>
            @if(filled($lesson->topic))
                <div><span class="font-medium text-gray-700">Topic:</span> {{ $lesson->topic }}</div>
            @endif
            @if(filled($lesson->homework))
                <div><span class="font-medium text-gray-700">Homework:</span> {{ $lesson->homework }}</div>
            @endif
            @if(filled($lesson->comments))
                <div><span class="font-medium text-gray-700">Comments:</span> {{ $lesson->comments }}</div>
            @endif
            @if($refund)
                <div class="font-medium text-amber-600">Refund requested</div>
            @endif
        </div>
    </template>
</span>
