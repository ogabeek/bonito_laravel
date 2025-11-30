@props(['students', 'stats'])

<div class="space-y-2">
    @foreach($students as $student)
        @php
            $s = $stats[$student->id] ?? ['total' => 0, 'completed' => 0, 'student_cancelled' => 0, 'teacher_cancelled' => 0, 'student_absent' => 0];
        @endphp
        <a href="{{ route('student.dashboard', $student) }}" class="block border rounded px-3 py-2 bg-white hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="font-medium text-sm text-gray-900">{{ $student->name }}</div>
                <x-student-stats-compact :stats="$s" class="text-[11px]" />
            </div>
        </a>
    @endforeach
</div>
