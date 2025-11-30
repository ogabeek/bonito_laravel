@props(['stats'])

<div {{ $attributes->merge(['class' => 'grid grid-cols-4 gap-px text-[8px] text-right']) }}>
    <div style="color: var(--color-status-completed); opacity: 0.8;">{{ $stats['completed'] ?? 0 }}</div>
    <div style="color: var(--color-status-student-cancelled); opacity: 0.8;">{{ $stats['student_cancelled'] ?? 0 }}</div>
    <div style="color: var(--color-status-cancelled); opacity: 0.8;">{{ $stats['teacher_cancelled'] ?? 0 }}</div>
    <div style="color: var(--color-status-absent); opacity: 0.8;">{{ $stats['student_absent'] ?? 0 }}</div>
</div>
