<?php

use App\Models\Student;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Student $student;

    #[Validate('nullable|string|max:10000')]
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
        <div class="mb-6 p-4 bg-white border rounded-lg shadow-sm">
            <textarea
                wire:model="notes"
                placeholder="Add here anything you would like to attach to be seen by the student"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            ></textarea>
            <div class="flex items-center gap-3 mt-2">
                <button type="button" wire:click="save" class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">
                    Save
                </button>
                @if($saved)
                    <span class="text-green-600 text-sm">✓ Saved</span>
                @endif
            </div>
        </div>
    @elseif($notes)
        {{-- Student view: just the content, no extra chrome --}}
        <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-lg whitespace-pre-wrap text-sm text-gray-700">{{ $notes }}</div>
    @endif
</div>
