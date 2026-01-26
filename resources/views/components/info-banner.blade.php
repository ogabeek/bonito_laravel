@props([
    'type' => 'info',     // info, success, warning, tip
    'icon' => null,
    'dismissible' => false,
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

<div {{ $attributes->merge(['class' => "border rounded-lg p-3 sm:p-4 text-sm {$colorClass}"]) }}
     @if($dismissible) x-data="{ show: true }" x-show="show" @endif>
    <div class="flex gap-3">
        @if($icon || $defaultIcon)
            <div class="text-lg shrink-0">{{ $icon ?? $defaultIcon }}</div>
        @endif
        <div class="flex-1">
            {{ $slot }}
        </div>
        @if($dismissible)
            <button @click="show = false" class="text-gray-400 hover:text-gray-600 shrink-0 text-lg leading-none">×</button>
        @endif
    </div>
</div>
