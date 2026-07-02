@props([
    'variant' => 'primary', // primary | secondary | danger | success
    'size' => 'md',         // md (px-4 py-2) | sm (px-3 py-1 text-sm) | xs (px-3 py-1 text-xs)
    'href' => null,         // renders an <a> styled as a button
])

@php
    $variants = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700',
        'secondary' => 'bg-gray-200 text-gray-700 hover:bg-gray-300',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'success' => 'bg-green-600 text-white hover:bg-green-700',
    ];
    $sizes = [
        'md' => 'px-4 py-2',
        'sm' => 'px-3 py-1 text-sm',
        'xs' => 'px-3 py-1 text-xs',
    ];
    $classes = 'inline-flex items-center justify-center gap-1 rounded font-medium transition-colors '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
