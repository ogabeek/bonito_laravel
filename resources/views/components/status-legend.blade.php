@props(['compact' => false])

@if($compact)
    <div class="flex flex-wrap items-center gap-2 text-[10px] text-gray-600">
        <span class="flex items-center gap-1">
            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: var(--color-status-completed);"></span>
            Done
        </span>
        <span class="flex items-center gap-1">
            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: var(--color-status-student-cancelled);"></span>
            C
        </span>
        <span class="flex items-center gap-1">
            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: var(--color-status-cancelled);"></span>
            CT
        </span>
        <span class="flex items-center gap-1">
            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: var(--color-status-absent);"></span>
            A
        </span>
    </div>
@else
    <div class="flex gap-4 text-sm">
        <div class="flex items-center gap-2">
            <div class="w-6 h-4 rounded" style="background: var(--color-status-completed-bg);"></div>
            <span>Completed</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-6 h-4 rounded" style="background: var(--color-status-absent-bg);"></div>
            <span>Student Absent</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-6 h-4 rounded" style="background: var(--color-status-student-cancelled-bg);"></div>
            <span>Student Cancelled</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-6 h-4 rounded" style="background: var(--color-status-cancelled-bg);"></div>
            <span>Teacher Cancelled</span>
        </div>
    </div>
@endif
