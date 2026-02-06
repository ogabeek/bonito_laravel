@props([
    'type' => 'info',     // info, success, warning, tip
    'icon' => null,
    'dismissible' => false,
    'id' => null,         // unique ID for persistent dismiss (localStorage)
])

@php
    $styles = [
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'warning' => 'bg-orange-50 border-orange-200 text-orange-800',
        'tip' => 'bg-purple-50 border-purple-200 text-purple-800',
    ];

    $icons = [
        'info' => 'ℹ️',
        'success' => '✅',
        'warning' => '⚠️',
        'tip' => '💡',
    ];

    $colorClass = $styles[$type] ?? $styles['info'];
    $defaultIcon = $icons[$type] ?? $icons['info'];
@endphp

@if($id)
    {{-- Persistent dismiss with "don't show again" option --}}
    <div x-data="{
            key: 'banner_{{ $id }}',
            hidden: false,
            dismissed: false,
            init() {
                this.hidden = localStorage.getItem(this.key) === 'hidden';
            },
            dismiss() {
                this.dismissed = true;
            },
            hideForever() {
                localStorage.setItem(this.key, 'hidden');
                this.hidden = true;
            }
         }"
         x-cloak>
        {{-- Full banner --}}
        <div x-show="!hidden && !dismissed" {{ $attributes->merge(['class' => "border rounded-lg p-3 sm:p-4 text-sm {$colorClass}"]) }}>
            <div class="flex gap-3">
                @if($icon || $defaultIcon)
                    <div class="text-lg shrink-0">{{ $icon ?? $defaultIcon }}</div>
                @endif
                <div class="flex-1">{{ $slot }}</div>
                <button @click="dismiss()" class="text-gray-400 hover:text-gray-600 shrink-0 text-lg leading-none">&times;</button>
            </div>
        </div>
        {{-- Minimal "don't show again" after dismiss --}}
        <div x-show="!hidden && dismissed" {{ $attributes->merge(['class' => "border rounded-lg p-3 sm:p-4 text-sm {$colorClass}"]) }}>
            <div class="flex justify-end">
                <button @click="hideForever()" class="text-xs opacity-60 hover:opacity-100 underline">Don't show this message again</button>
            </div>
        </div>
    </div>
@elseif($dismissible)
    {{-- Simple dismiss: just hides for current page view --}}
    <div x-data="{ show: true }" x-show="show" {{ $attributes->merge(['class' => "border rounded-lg p-3 sm:p-4 text-sm {$colorClass}"]) }}>
        <div class="flex gap-3">
            @if($icon || $defaultIcon)
                <div class="text-lg shrink-0">{{ $icon ?? $defaultIcon }}</div>
            @endif
            <div class="flex-1">{{ $slot }}</div>
            <button @click="show = false" class="text-gray-400 hover:text-gray-600 shrink-0 text-lg leading-none">&times;</button>
        </div>
    </div>
@else
    {{-- Non-dismissible --}}
    <div {{ $attributes->merge(['class' => "border rounded-lg p-3 sm:p-4 text-sm {$colorClass}"]) }}>
        <div class="flex gap-3">
            @if($icon || $defaultIcon)
                <div class="text-lg shrink-0">{{ $icon ?? $defaultIcon }}</div>
            @endif
            <div class="flex-1">{{ $slot }}</div>
        </div>
    </div>
@endif
