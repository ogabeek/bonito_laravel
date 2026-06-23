@props(['status', 'size' => 6, 'title' => null])

@php
    // Size in pixels, not Tailwind classes (to avoid dynamic class limitations)
    $sizeStyle = "{$size}px";
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-block rounded-full']) }}
    style="width: {{ $sizeStyle }}; height: {{ $sizeStyle }}; background-color: {{ $status->dotColor() }}"
    title="{{ $title ?? $status->label() }}"
></span>
