<?php

use App\Models\Student;
use App\Models\Teacher;
use Livewire\Volt\Component;

new class extends Component
{
    public Student $student;

    public ?Teacher $teacher = null;

    public bool $open = false;

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
            <div class="flex items-center justify-between gap-3">
                <button type="button" wire:click="$toggle('open')" class="min-w-0 flex-1 text-left">
                    <div class="truncate font-semibold text-gray-800">With {{ $teacher->name }}</div>
                    <div class="text-xs text-gray-500">Contact and lesson links</div>
                </button>

                <div class="flex shrink-0 items-center gap-2">
                    @if($teacher->zoom_link)
                        <a href="{{ $teacher->zoom_link }}" target="_blank" rel="noopener"
                           class="inline-flex self-start rounded bg-blue-600/80 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-600 sm:text-sm">
                            Join Zoom
                        </a>
                    @endif
                    @if($teacher->contact || $teacher->zoom_id || $teacher->zoom_passcode)
                        <button type="button" wire:click="$toggle('open')" class="rounded border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700">
                            {{ $open ? 'Hide' : 'Details' }}
                        </button>
                    @endif
                </div>
            </div>

            @if($open && ($teacher->contact || $teacher->zoom_id || $teacher->zoom_passcode))
                <div class="mt-3 border-t border-gray-100 pt-3 text-xs text-gray-500">
                    @if($teacher->contact)
                        <a href="mailto:{{ $teacher->contact }}" class="block break-all underline decoration-gray-300 underline-offset-2 hover:text-gray-700">
                            {{ $teacher->contact }}
                        </a>
                    @endif
                    @if($teacher->zoom_id || $teacher->zoom_passcode)
                        <div class="mt-1">
                            @if($teacher->zoom_id)ID: {{ $teacher->zoom_id }}@endif
                            @if($teacher->zoom_id && $teacher->zoom_passcode) · @endif
                            @if($teacher->zoom_passcode)Pass: {{ $teacher->zoom_passcode }}@endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
