@props(['value'])

@php
    $classes = 'text-gray-600';

    if ($value !== null) {
        if ($value < 0) {
            $classes = 'text-red-600';
        } elseif ($value === 0) {
            $classes = 'text-amber-600';
        } elseif ($value <= 3) {
            $classes = 'text-yellow-600';
        } elseif ($value <= 10) {
            $classes = 'text-green-600';
        } else {
            $classes = 'text-emerald-600';
        }
    }
@endphp

<span {{ $attributes->merge(['class' => "inline-block px-0.5 py-0.25 text-[9px] font-semibold text-center $classes"]) }}>
    {{ $value ?? '—' }}
</span>
