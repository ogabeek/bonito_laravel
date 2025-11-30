@props(['stats'])

<div {{ $attributes->merge(['class' => 'grid grid-cols-4 gap-4 text-[12px] text-center font-semibold']) }}>
    <div style="color: var(--color-status-completed);">{{ $stats['completed'] ?? 0 }}</div>
    <div style="color: var(--color-status-student-cancelled);">{{ $stats['student_cancelled'] ?? 0 }}</div>
    <div style="color: var(--color-status-cancelled);">{{ $stats['teacher_cancelled'] ?? 0 }}</div>
    <div style="color: var(--color-status-absent);">{{ $stats['student_absent'] ?? 0 }}</div>
</div>
