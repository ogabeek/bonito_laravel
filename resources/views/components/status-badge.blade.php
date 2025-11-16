@props(['status'])

@php
    $colors = [
        'completed' => 'bg-green-100 text-green-700 border-green-300',
        'student_absent' => 'bg-red-100 text-red-700 border-red-300',
        'teacher_cancelled' => 'bg-orange-100 text-orange-700 border-orange-300',
    ];
    
    $labels = [
        'completed' => '✓ Completed',
        'student_absent' => '⚠ Student Absent',
        'teacher_cancelled' => '🚫 Cancelled',
    ];
    
    $colorClass = $colors[$status] ?? 'bg-gray-100 text-gray-700 border-gray-300';
    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-1 text-xs font-medium border rounded $colorClass"]) }}>
    {{ $label }}
</span>
