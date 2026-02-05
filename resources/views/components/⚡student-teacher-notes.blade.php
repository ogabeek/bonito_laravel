<?php

use App\Models\Student;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Student $student;

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->notes = $this->student->teacher_notes ?? '';
    }

    public function updatedNotes(): void
    {
        $this->save();
    }

    public function save(): void
    {
        $this->validate();

        $this->student->update([
            'teacher_notes' => $this->notes,
        ]);

        // Track who is making the change
        $causer = null;
        if (session('teacher_id')) {
            $causer = \App\Models\Teacher::find(session('teacher_id'));
        }

        activity()
            ->performedOn($this->student)
            ->causedBy($causer)
            ->withProperties([
                'action' => 'updated_teacher_notes',
                'student_name' => $this->student->name,
            ])
            ->log('updated teacher notes');

        $this->saved = true;
    }
}; ?>

<div wire:key="student-notes-{{ $student->id }}">
    @if(session('teacher_id') || session('admin_authenticated'))
        {{-- Teacher/Admin view: editable --}}
        <div class="mb-6">
            <textarea
                wire:model="notes"
                placeholder="Notes for student..."
                rows="3"
                maxlength="1000"
                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
            <div class="flex items-center gap-2 mt-2">
                <button type="button" wire:click="save" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                    Save
                </button>
                @if($saved)
                    <span class="text-green-600 text-sm">✓</span>
                @endif
                @error('notes')
                    <span class="text-red-600 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>
    @elseif($notes)
        {{-- Student view: just the content, no extra chrome --}}
        <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-lg whitespace-pre-wrap text-sm text-gray-700">{{ $notes }}</div>
    @endif
</div>
