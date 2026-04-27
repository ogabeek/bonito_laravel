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
        <div class="text-sm">
            <div class="flex items-center justify-between gap-3">
                <button type="button" wire:click="$toggle('open')" class="min-w-0 flex-1 text-left">
                    <div class="truncate font-semibold text-gray-800">With {{ $teacher->name }}</div>
                </button>

                <div class="flex shrink-0 items-center gap-2">
                    @if($teacher->zoom_link)
                        <a href="{{ $teacher->zoom_link }}" target="_blank" rel="noopener"
                           class="inline-flex h-8 w-24 items-center justify-center rounded-md bg-blue-600/80 px-3 text-xs font-medium text-white transition-colors hover:bg-blue-600">
                            Join Zoom
                        </a>
                    @endif
                    @if($teacher->contact || $teacher->zoom_id || $teacher->zoom_passcode)
                        <button type="button" wire:click="$toggle('open')" aria-expanded="{{ $open ? 'true' : 'false' }}" class="inline-flex h-8 w-24 items-center justify-center gap-1 rounded-md border border-gray-200 px-2.5 text-xs font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700">
                            <span>Details</span>
                            <svg class="{{ $open ? 'rotate-180' : '' }} h-3 w-3 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            @if($open && ($teacher->contact || $teacher->zoom_id || $teacher->zoom_passcode))
                <div class="mt-3 text-xs text-gray-500">
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
