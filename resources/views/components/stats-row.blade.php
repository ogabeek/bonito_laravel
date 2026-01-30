@props([
    'stats',
    'showLabels' => false,
    'size' => 'sm',
])

@php
    $sizeClasses = match($size) {
        'xs' => 'text-xs gap-1',
        'sm' => 'text-sm gap-2',
        'md' => 'text-base gap-3',
        'lg' => 'text-lg gap-4',
        default => 'text-sm gap-2',
    };
    
    $statItems = [
        ['key' => 'completed', 'color' => '--color-status-completed', 'label' => 'Done'],
        ['key' => 'student_cancelled', 'color' => '--color-status-student-cancelled', 'label' => 'C'],
        ['key' => 'teacher_cancelled', 'color' => '--color-status-cancelled', 'label' => 'CT'],
        ['key' => 'student_absent', 'color' => '--color-status-absent', 'label' => 'A'],
    ];
@endphp

<div {{ $attributes->merge(['class' => "flex items-center {$sizeClasses}"]) }}>
    @if(isset($stats['total']))
        <span class="font-semibold text-gray-800">{{ $stats['total'] }}</span>
        <span class="text-gray-400">|</span>
    @endif
    
    @foreach($statItems as $item)
        <span style="color: var({{ $item['color'] }});">
            {{ $stats[$item['key']] ?? 0 }}@if($showLabels) {{ $item['label'] }}@endif
        </span>
    @endforeach
</div>
