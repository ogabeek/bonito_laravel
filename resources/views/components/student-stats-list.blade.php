@props(['students', 'stats', 'totalStats' => null, 'showBalance' => false, 'editable' => false, 'nudges' => []])

@php
    $holidayValue = \App\Enums\StudentStatus::HOLIDAY->value;
    $activeValue = \App\Enums\StudentStatus::ACTIVE->value;
    $fieldClass = 'w-full rounded-md border-gray-200 bg-white text-sm text-gray-700 focus:border-gray-400 focus:ring-gray-300';
@endphp

<div class="space-y-1">
    <div class="flex justify-between items-center px-3">
        <div></div>
        <div class="grid grid-cols-4 gap-4 text-[11px] text-gray-500 text-center w-40">
            <div title="Completed">Done</div>
            <div title="Cancelled by student">C</div>
            <div title="Cancelled by teacher">CT</div>
            <div title="Student absent">A</div>
        </div>
    </div>

    @if($totalStats)
        <div class="flex items-center justify-between gap-3 px-3 py-2 rounded bg-gray-50">
            <div class="font-medium text-sm text-gray-900">All</div>
            <x-student-stats-compact :stats="$totalStats" class="w-40" />
        </div>
    @endif

    @foreach($students as $student)
        @php
            $s = $stats[$student->id] ?? ['total' => 0, 'completed' => 0, 'student_cancelled' => 0, 'teacher_cancelled' => 0, 'student_absent' => 0];
        @endphp

        @if($editable)
            @php
                $nudge = $nudges[$student->id] ?? null;
                $nudgeRing = match($nudge) {
                    'inactivity' => 'ring-2 ring-amber-400 animate-pulse',
                    'reactivation' => 'ring-2 ring-emerald-400 animate-pulse',
                    default => '',
                };
            @endphp
            <div wire:key="student-row-{{ $student->id }}" class="flex items-center justify-between gap-3 px-3 py-2 rounded hover:bg-gray-50">
                <div class="flex items-center gap-2 min-w-0">
                    {{-- Status dot: click to change status. Holiday adds dates; any non-active status adds a note. --}}
                    <div
                        class="relative flex-shrink-0"
                        x-data="{
                            open: false,
                            status: @js($student->status->value),
                            start: @js($student->vacation_starts_on?->format('Y-m-d') ?? ''),
                            end: @js($student->vacation_ends_on?->format('Y-m-d') ?? ''),
                            note: @js($student->status_note ?? ''),
                            saving: false,
                            save() {
                                this.saving = true;
                                $wire.saveStudentStatus({{ $student->id }}, this.status, this.start, this.end, this.note)
                                    .then(() => { this.open = false; })
                                    .finally(() => { this.saving = false; });
                            }
                        }"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                    >
                        <button type="button" @click="open = ! open" :aria-expanded="open" title="Change status" class="flex items-center rounded p-1 hover:bg-gray-200 {{ $nudgeRing }}">
                            <x-student-status-dot :status="$student->status" />
                        </button>

                        <div x-show="open" x-cloak x-transition class="absolute left-0 top-7 z-30 w-80 space-y-4 rounded-lg border border-gray-200 bg-white p-4 text-left shadow-lg">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Change status</div>
                                <div class="mt-1 text-xs text-gray-500">Choose the student’s current status.</div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                @foreach(\App\Enums\StudentStatus::cases() as $opt)
                                    <button
                                        type="button"
                                        @click="status = '{{ $opt->value }}'"
                                        :aria-pressed="status === '{{ $opt->value }}'"
                                        :class="status === '{{ $opt->value }}'
                                            ? '{{ $opt->badgeClass() }} border-transparent ring-1 ring-gray-300'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                                        class="flex items-center gap-2 rounded-md border px-3 py-2 text-left text-xs font-medium transition"
                                    >
                                        <x-student-status-dot :status="$opt" :size="7" />
                                        <span class="flex-1">{{ $opt->label() }}</span>
                                        <svg x-show="status === '{{ $opt->value }}'" x-cloak class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.051l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.816a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                @endforeach
                            </div>

                            {{-- Holiday → vacation dates (stacked, full-width so each date reads clearly) --}}
                            <div x-show="status === '{{ $holidayValue }}'" x-cloak class="space-y-2.5">
                                <div class="text-xs font-medium text-gray-500">Vacation dates</div>

                                @foreach([
                                    ['label' => 'Start', 'model' => 'start', 'ref' => 'holidayStart'],
                                    ['label' => 'End', 'model' => 'end', 'ref' => 'holidayEnd'],
                                ] as $dateField)
                                    <div
                                        class="flex cursor-pointer items-center gap-3 rounded-md border border-gray-200 bg-white px-3 py-2 transition hover:border-gray-300 hover:bg-gray-50 focus-within:border-gray-400 focus-within:ring-1 focus-within:ring-gray-300"
                                        @click="$refs.{{ $dateField['ref'] }}.focus(); $refs.{{ $dateField['ref'] }}.showPicker?.()"
                                    >
                                        <label for="student-{{ $student->id }}-holiday-{{ $dateField['model'] }}" class="w-10 shrink-0 cursor-pointer text-xs font-medium text-gray-500">
                                            {{ $dateField['label'] }}
                                        </label>
                                        <input
                                            id="student-{{ $student->id }}-holiday-{{ $dateField['model'] }}"
                                            x-ref="{{ $dateField['ref'] }}"
                                            type="date"
                                            x-model="{{ $dateField['model'] }}"
                                            @click.stop="$el.showPicker?.()"
                                            class="min-w-0 flex-1 cursor-pointer border-0 bg-transparent p-0 text-sm text-gray-700 focus:ring-0"
                                        >
                                    </div>
                                @endforeach
                            </div>

                            {{-- Any non-active status → an optional note --}}
                            <div x-show="status !== '{{ $activeValue }}'" x-cloak>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Comment <span class="font-normal text-gray-400">(optional)</span></label>
                                <textarea x-model="note" rows="2" placeholder="e.g. Moved abroad, stopped in May" class="{{ $fieldClass }} p-2"></textarea>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <button type="button" @click="save()" :disabled="saving" class="rounded-md bg-gray-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-900 disabled:opacity-50">Save</button>
                                <button type="button" @click="open = false" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('student.dashboard', $student) }}" class="font-medium text-sm text-gray-900 truncate hover:underline">{{ $student->name }}</a>

                    @if($nudge === 'inactivity')
                        <span class="flex-shrink-0 whitespace-nowrap text-xs font-medium text-amber-600">← no class in 7 days — still active?</span>
                    @elseif($nudge === 'reactivation')
                        <span class="flex-shrink-0 whitespace-nowrap text-xs font-medium text-emerald-600">← classes resumed — mark active?</span>
                    @elseif($student->status_note)
                        <span class="truncate text-xs italic text-gray-400" title="{{ $student->status_note }}">— {{ $student->status_note }}</span>
                    @endif

                    @if($showBalance && $student->class_balance !== null)
                        <x-balance-badge :value="$student->class_balance" class="ml-1 text-sm" />
                    @endif
                </div>
                <x-student-stats-compact :stats="$s" class="w-40 flex-shrink-0" />
            </div>
        @else
            <a href="{{ route('student.dashboard', $student) }}" class="block px-3 py-2 rounded hover:bg-gray-50">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <x-student-status-dot :status="$student->status" />
                        <div class="font-medium text-sm text-gray-900 truncate">{{ $student->name }}</div>
                        @if($showBalance && $student->class_balance !== null)
                            <x-balance-badge :value="$student->class_balance" class="ml-1 text-sm" />
                        @endif
                    </div>
                    <x-student-stats-compact :stats="$s" class="w-40" />
                </div>
            </a>
        @endif
    @endforeach
</div>
