<?php

use App\Models\Student;
use App\Models\Teacher;
use Livewire\Volt\Component;

new class extends Component
{
    public Student $student;

    public ?Teacher $teacher = null;

    public function mount(): void
    {
        // Get the teacher who has taught this student the most
        $teacherId = $this->student->lessons()
            ->selectRaw('teacher_id, COUNT(*) as lesson_count')
            ->groupBy('teacher_id')
            ->orderByDesc('lesson_count')
            ->value('teacher_id');

        if ($teacherId) {
            $this->teacher = Teacher::find($teacherId);
        }
    }
}; ?>

<div>
    @if($teacher && ($teacher->contact || $teacher->zoom_link))
        <div class="mb-6 flex items-center gap-3 text-sm">
            @if($teacher->zoom_link)
                <a
                    href="{{ $teacher->zoom_link }}"
                    target="_blank"
                    rel="noopener"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 relative group"
                >
                    Join Zoom
                    @if($teacher->zoom_id || $teacher->zoom_passcode)
                        <span class="hidden group-hover:block absolute left-0 top-full mt-1 bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap z-10">
                            @if($teacher->zoom_id)ID: {{ $teacher->zoom_id }}@endif@if($teacher->zoom_id && $teacher->zoom_passcode) | @endif@if($teacher->zoom_passcode)Pass: {{ $teacher->zoom_passcode }}@endif
                        </span>
                    @endif
                </a>
            @endif
            @if($teacher->contact)
                <a href="mailto:{{ $teacher->contact }}" class="text-gray-600 hover:text-blue-600">{{ $teacher->contact }}</a>
            @endif
        </div>
    @endif
</div>
