<?php

use App\Models\Student;
use App\Models\Teacher;
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
        abort_unless($this->canEdit(), 403);

        $this->validate();

        $this->student->update([
            'teacher_notes' => $this->notes,
            'materials_url' => blank($this->materialsUrl) ? null : trim($this->materialsUrl),
        ]);

        $causer = null;
        if (session('teacher_id')) {
            $causer = Teacher::find(session('teacher_id'));
        }

        activity()
            ->performedOn($this->student)
            ->causedBy($causer)
            ->withProperties([
                'action' => 'updated_student_resources',
                'student_name' => $this->student->name,
            ])
            ->log('updated student resources');

        $this->saved = true;
    }

    public function canEdit(): bool
    {
        return (bool) session('teacher_id') || (bool) session('admin_authenticated');
    }
}; ?>

<div wire:key="student-resources-{{ $student->id }}">
    @if($this->canEdit())
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
                class="min-h-[80px] w-full resize-none overflow-hidden rounded-md border border-gray-200 bg-white p-3 text-sm text-gray-700 focus:border-gray-400 focus:ring-2 focus:ring-gray-300"
            ></textarea>
            <label for="student-{{ $student->id }}-materials" class="mt-3 mb-1 block text-xs font-medium text-gray-500">Materials link</label>
            <input
                id="student-{{ $student->id }}-materials"
                type="url"
                wire:model.blur="materialsUrl"
                placeholder="https://..."
                class="w-full rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300"
            >
            <div class="flex items-center gap-2 mt-2">
                <button type="button" wire:click="save" class="h-8 rounded-md bg-gray-800 px-3 text-sm font-medium text-white hover:bg-gray-900">
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
    @endif
</div>
