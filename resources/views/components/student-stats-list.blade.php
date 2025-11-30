@props(['students', 'stats', 'totalStats' => null, 'showBalance' => false])

<div class="space-y-1">
    <div class="flex justify-between items-center px-3">
        <div class="text-sm font-semibold text-gray-700">Students</div>
        <div class="grid grid-cols-4 gap-4 text-[11px] text-gray-500 text-center w-40">
            <div>Done</div>
            <div>C</div>
            <div>CT</div>
            <div>A</div>
        </div>
    </div>

    @if($totalStats)
        <div class="flex items-center justify-between gap-3 px-3 py-2 rounded bg-gray-50">
            <div class="font-medium text-sm text-gray-900">All</div>
            <x-student-stats-compact :stats="$totalStats" class="w-40" />
        </div>
    @endif
    @foreach($students as $student)
        @php
            $s = $stats[$student->id] ?? ['total' => 0, 'completed' => 0, 'student_cancelled' => 0, 'teacher_cancelled' => 0, 'student_absent' => 0];
        @endphp
        <a href="{{ route('student.dashboard', $student) }}" class="block px-3 py-2 rounded hover:bg-gray-50">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <x-student-status-dot :status="$student->status" />
                    <div class="font-medium text-sm text-gray-900 truncate">{{ $student->name }}</div>
                    @if($showBalance && $student->class_balance !== null)
                        <x-balance-badge :value="$student->class_balance" class="ml-1" />
                    @endif
                </div>
                <x-student-stats-compact :stats="$s" class="w-40" />
            </div>
        </a>
    @endforeach
</div>
