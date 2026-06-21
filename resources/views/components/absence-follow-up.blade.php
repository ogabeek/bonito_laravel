@props(['flags'])

@php
    $items = [
        'needs_recovery' => 'Needs recovery',
        'reminder_sent' => 'Reminder sent',
        'no_response' => 'No response',
    ];
@endphp

<div {{ $attributes->class(['flex flex-wrap gap-1.5']) }}>
    @foreach($items as $key => $label)
        @php($enabled = (bool) ($flags[$key] ?? false))
        <span
            aria-label="{{ $label }}: {{ $enabled ? 'yes' : 'no' }}"
            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-medium {{ $enabled ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-400' }}"
        >
            <span class="h-1.5 w-1.5 rounded-full {{ $enabled ? 'bg-green-500' : 'bg-gray-300' }}"></span>
            {{ $label }}
        </span>
    @endforeach
</div>
