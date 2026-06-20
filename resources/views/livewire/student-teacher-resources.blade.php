<?php

use App\Models\Student;
use App\Models\Teacher;
use Livewire\Volt\Component;

new class extends Component
{
    public Student $student;

    public string $goal = '';

    public string $description = '';

    public string $notes = '';

    public string $materialsUrl = '';

    public string $vacationStart = '';

    public string $vacationEnd = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->goal = $this->student->goal ?? '';
        $this->description = $this->student->description ?? '';
        $this->notes = $this->student->teacher_notes ?? '';
        $this->materialsUrl = $this->student->materials_url ?? '';
        $this->vacationStart = $this->student->vacation_starts_on?->format('Y-m-d') ?? '';
        $this->vacationEnd = $this->student->vacation_ends_on?->format('Y-m-d') ?? '';
    }

    protected function rules(): array
    {
        return [
            'goal' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
            'materialsUrl' => 'nullable|url|max:2048',
            'vacationStart' => 'nullable|date',
            'vacationEnd' => 'nullable|date',
        ];
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

        if ($this->vacationStart && $this->vacationEnd && $this->vacationEnd < $this->vacationStart) {
            $this->addError('vacationEnd', 'Vacation end must be on or after the start date.');

            return;
        }

        $this->student->update([
            'goal' => blank($this->goal) ? null : trim($this->goal),
            'description' => blank($this->description) ? null : trim($this->description),
            'teacher_notes' => $this->notes,
            'materials_url' => blank($this->materialsUrl) ? null : trim($this->materialsUrl),
            'vacation_starts_on' => $this->vacationStart ?: null,
            'vacation_ends_on' => $this->vacationEnd ?: null,
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
        <div class="mb-6 space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-3 sm:p-4">
            <div class="text-xs font-medium text-gray-500">From teacher</div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="student-{{ $student->id }}-goal" class="mb-1 block text-xs font-medium text-gray-500">Goal</label>
                    <input
                        id="student-{{ $student->id }}-goal"
                        type="text"
                        wire:model.blur="goal"
                        maxlength="500"
                        placeholder="e.g. Reach B2 by summer"
                        class="w-full rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300"
                    >
                </div>
                <div>
                    <label for="student-{{ $student->id }}-vacation-start" class="mb-1 block text-xs font-medium text-gray-500">Vacation period</label>
                    <div class="flex items-center gap-2">
                        <input
                            id="student-{{ $student->id }}-vacation-start"
                            type="date"
                            wire:model.blur="vacationStart"
                            class="w-full rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300"
                        >
                        <span class="text-gray-400">–</span>
                        <input
                            id="student-{{ $student->id }}-vacation-end"
                            type="date"
                            wire:model.blur="vacationEnd"
                            class="w-full rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300"
                        >
                    </div>
                </div>
            </div>

            <div>
                <label for="student-{{ $student->id }}-description" class="mb-1 block text-xs font-medium text-gray-500">Description</label>
                <textarea
                    id="student-{{ $student->id }}-description"
                    wire:model.blur="description"
                    rows="2"
                    maxlength="2000"
                    placeholder="Background, level, preferences…"
                    class="w-full rounded-md border border-gray-200 bg-white p-3 text-sm text-gray-700 focus:border-gray-400 focus:ring-2 focus:ring-gray-300"
                ></textarea>
            </div>

            <div>
                <label for="student-{{ $student->id }}-notes" class="mb-1 block text-xs font-medium text-gray-500">Teacher notes <span class="text-gray-400">(shown on student page)</span></label>
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
            </div>

            <div>
                <label for="student-{{ $student->id }}-materials" class="mb-1 block text-xs font-medium text-gray-500">Materials link</label>
                <input
                    id="student-{{ $student->id }}-materials"
                    type="url"
                    wire:model.blur="materialsUrl"
                    placeholder="https://..."
                    class="w-full rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300"
                >
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="save" class="h-8 rounded-md bg-gray-800 px-3 text-sm font-medium text-white hover:bg-gray-900">
                    Save
                </button>
                @if($saved)
                    <span class="text-green-600 text-sm">✓ Saved</span>
                @endif
                @error('goal') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                @error('description') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                @error('notes') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                @error('materialsUrl') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                @error('vacationStart') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                @error('vacationEnd') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
    @endif
</div>
