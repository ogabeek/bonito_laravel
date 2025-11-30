@props(['value'])

@php
    $classes = 'bg-gray-100 text-gray-600';

    if ($value !== null) {
        if ($value < 0) {
            $classes = 'bg-red-100 text-red-700';
        } elseif ($value === 0) {
            $classes = 'bg-amber-100 text-amber-700';
        } elseif ($value <= 3) {
            $classes = 'bg-yellow-100 text-yellow-700';
        } elseif ($value <= 10) {
            $classes = 'bg-green-100 text-green-700';
        } else {
            $classes = 'bg-emerald-100 text-emerald-700';
        }
    }
@endphp

<span {{ $attributes->merge(['class' => "inline-block px-0.5 py-0.25 text-[8px] font-semibold rounded w-full text-center $classes"]) }}>
    {{ $value ?? '—' }}
</span>
