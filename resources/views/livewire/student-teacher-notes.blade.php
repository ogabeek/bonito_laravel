<?php

use App\Models\Student;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component
{
    public Student $student;

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    #[Validate('nullable|url|max:2048')]
    public string $materialsUrl = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->notes = $this->student->teacher_notes ?? '';
        $this->materialsUrl = $this->student->materials_url ?? '';
    }

    public function updatedNotes(): void
    {
        $this->save();
    }

    public function updatedMaterialsUrl(): void
    {
        $this->save();
    }

    public function save(): void
    {
        $this->validate();

        $this->student->update([
            'teacher_notes' => $this->notes,
            'materials_url' => blank($this->materialsUrl) ? null : trim($this->materialsUrl),
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
            <div class="mb-3 text-xs font-medium text-gray-500">From teacher</div>
            <label for="student-{{ $student->id }}-notes" class="mb-1 block text-xs font-medium text-gray-500">Teacher notes</label>
            <textarea
                id="student-{{ $student->id }}-notes"
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
            <label for="student-{{ $student->id }}-materials" class="mt-3 mb-1 block text-xs font-medium text-gray-500">Materials link</label>
            <input
                id="student-{{ $student->id }}-materials"
                type="url"
                wire:model.blur="materialsUrl"
                placeholder="https://..."
                class="w-full rounded border-gray-200 bg-white text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300"
            >
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
                @error('materialsUrl')
                    <span class="text-red-600 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>
    @elseif($notes || $student->materials_url)
        {{-- Student view: read-only with clickable links --}}
        <div class="space-y-4">
            @if($notes)
                <div class="rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700">
                    <div class="border-b border-gray-200 px-4 py-2 text-xs font-medium text-gray-500">From teacher</div>
                    <div class="whitespace-pre-wrap px-4 py-3 [&_a]:text-gray-600 [&_a]:underline [&_a]:decoration-gray-300 [&_a]:underline-offset-2 hover:[&_a]:text-gray-800">{!! Str::linkify($notes) !!}</div>
                </div>
            @endif
            @if($student->materials_url)
                <a href="{{ $student->materials_url }}" target="_blank" rel="noopener" class="flex w-full items-center justify-center rounded bg-gray-800 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-900">
                    Class materials
                </a>
            @endif
        </div>
    @endif
</div>
