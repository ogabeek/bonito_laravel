@props(['stats'])

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2 text-xs']) }}>
    <span class="inline-flex items-center gap-1 px-2 py-1 rounded" style="background: var(--color-status-completed-bg); color: var(--color-status-completed);">
        {{ $stats['completed'] ?? 0 }} Done
    </span>
    <span class="inline-flex items-center gap-1 px-2 py-1 rounded" style="background: var(--color-status-student-cancelled-bg); color: var(--color-status-student-cancelled);">
        {{ $stats['student_cancelled'] ?? 0 }} C
    </span>
    <span class="inline-flex items-center gap-1 px-2 py-1 rounded" style="background: var(--color-status-cancelled-bg); color: var(--color-status-cancelled);">
        {{ $stats['teacher_cancelled'] ?? 0 }} CT
    </span>
    <span class="inline-flex items-center gap-1 px-2 py-1 rounded" style="background: var(--color-status-absent-bg); color: var(--color-status-absent);">
        {{ $stats['student_absent'] ?? 0 }} A
    </span>
</div>
