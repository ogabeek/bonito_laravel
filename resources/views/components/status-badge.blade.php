@props(['status'])

@php
    $classes = [
        'completed' => 'status-completed',
        'student_absent' => 'status-absent',
        'student_cancelled' => 'status-student-cancelled',
        'teacher_cancelled' => 'status-cancelled',
    ];
    
    $labels = [
        'completed' => '✓ Completed',
        'student_absent' => '⚠ Student Absent',
        'student_cancelled' => '📘 Student Cancelled',
        'teacher_cancelled' => '🚫 Cancelled',
    ];
    
    $statusClass = $classes[$status] ?? '';
    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-1 text-xs font-medium border rounded $statusClass"]) }}>
    {{ $label }}
</span>
