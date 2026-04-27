<?php

use App\Models\Student;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

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
        {{-- Teacher/Admin view: editable, styled to match student view --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-3 sm:p-4">
            <div class="mb-2 text-xs font-medium text-gray-500">Teacher notes</div>
            <textarea
                wire:model="notes"
                placeholder="Add anything here you want to be displayed on student page (links, text, contacts etc...)"
                maxlength="1000"
                x-data="{
                    resize() {
                        $el.style.height = 'auto';
                        $el.style.height = $el.scrollHeight + 'px';
                    }
                }"
                x-init="resize(); Livewire.hook('morph.updated', ({ el }) => { if (el === $el) resize() })"
                x-on:input="resize()"
                class="w-full p-3 bg-white border border-gray-200 rounded text-sm text-gray-700 focus:ring-2 focus:ring-gray-300 focus:border-gray-400 resize-none overflow-hidden min-h-[80px]"
            ></textarea>
            <div class="flex items-center gap-2 mt-2">
                <button type="button" wire:click="save" class="px-3 py-1 bg-gray-800 text-white rounded text-sm hover:bg-gray-900">
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
        {{-- Student view: read-only with clickable links --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700">
            <div class="border-b border-gray-200 px-4 py-2 text-xs font-medium text-gray-500">Teacher notes</div>
            <div class="whitespace-pre-wrap px-4 py-3 [&_a]:text-gray-600 [&_a]:underline [&_a]:decoration-gray-300 [&_a]:underline-offset-2 hover:[&_a]:text-gray-800">{!! Str::linkify($notes) !!}</div>
        </div>
    @endif
</div>
