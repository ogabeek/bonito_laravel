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
        $this->teacher = $this->student->lessons()
            ->with('teacher')
            ->orderByDesc('class_date')
            ->first()
            ?->teacher
            ?? $this->student->teachers()->orderBy('name')->first();
    }
}; ?>

<div>
    @if($teacher)
        <div class="mb-3 rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm sm:p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="text-xs font-medium text-gray-400">Teacher</div>
                    <div class="font-semibold text-gray-800">{{ $teacher->name }}</div>
                    @if($teacher->contact)
                        <a href="mailto:{{ $teacher->contact }}" class="mt-1 block break-all text-gray-500 underline decoration-gray-300 underline-offset-2 hover:text-gray-700">
                            {{ $teacher->contact }}
                        </a>
                    @endif
                    @if($teacher->zoom_id || $teacher->zoom_passcode)
                        <div class="mt-1 text-xs text-gray-500">
                            @if($teacher->zoom_id)ID: {{ $teacher->zoom_id }}@endif
                            @if($teacher->zoom_id && $teacher->zoom_passcode) · @endif
                            @if($teacher->zoom_passcode)Pass: {{ $teacher->zoom_passcode }}@endif
                        </div>
                    @endif
                </div>

                @if($teacher->zoom_link)
                    <a href="{{ $teacher->zoom_link }}" target="_blank" rel="noopener"
                       class="inline-flex self-start rounded bg-blue-600/80 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-600 sm:text-sm">
                        Join Zoom
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
