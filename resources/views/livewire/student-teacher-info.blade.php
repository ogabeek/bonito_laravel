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
        // Get the teacher from the most recent lesson
        $this->teacher = $this->student->lessons()
            ->with('teacher')
            ->orderByDesc('class_date')
            ->first()
            ?->teacher;
    }
}; ?>

<div>
    @if($teacher && ($teacher->contact || $teacher->zoom_link))
        <div class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
            @if($teacher->zoom_link)
                <a href="{{ $teacher->zoom_link }}" target="_blank" rel="noopener"
                   class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Join Zoom
                </a>
                @if($teacher->zoom_id || $teacher->zoom_passcode)
                    <span class="text-xs text-gray-500">
                        @if($teacher->zoom_id)ID: {{ $teacher->zoom_id }}@endif
                        @if($teacher->zoom_id && $teacher->zoom_passcode) · @endif
                        @if($teacher->zoom_passcode)Pass: {{ $teacher->zoom_passcode }}@endif
                    </span>
                @endif
            @endif
            @if($teacher->contact)
                <a href="mailto:{{ $teacher->contact }}" class="text-gray-600 hover:text-blue-600">{{ $teacher->contact }}</a>
            @endif
        </div>
    @endif
</div>
