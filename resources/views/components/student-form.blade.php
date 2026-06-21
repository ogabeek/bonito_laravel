@props(['student' => null, 'mode' => 'create'])

<div class="space-y-3">
    <div class="grid grid-cols-2 gap-4">
        <div>
            @if($mode === 'edit')
                <label class="block text-sm font-medium mb-1">Student Name *</label>
            @endif
            <input
                type="text"
                name="name"
                value="{{ old('name', $student?->name) }}"
                placeholder="{{ $mode === 'create' ? 'Student Name *' : '' }}"
                required
                class="w-full px-3 py-2 border rounded">
        </div>

        <div>
            @if($mode === 'edit')
                <label class="block text-sm font-medium mb-1">Parent Name</label>
            @endif
            <input
                type="text"
                name="parent_name"
                value="{{ old('parent_name', $student?->parent_name) }}"
                placeholder="{{ $mode === 'create' ? 'Parent Name' : '' }}"
                class="w-full px-3 py-2 border rounded">
        </div>

        <div>
            @if($mode === 'edit')
                <label class="block text-sm font-medium mb-1">Email</label>
            @endif
            <input
                type="email"
                name="email"
                value="{{ old('email', $student?->email) }}"
                placeholder="{{ $mode === 'create' ? 'Email' : '' }}"
                class="w-full px-3 py-2 border rounded">
        </div>

        <div>
            @if($mode === 'edit')
                <label class="block text-sm font-medium mb-1">Goal</label>
            @endif
            <input
                type="text"
                name="goal"
                value="{{ old('goal', $student?->goal) }}"
                placeholder="{{ $mode === 'create' ? 'Goal' : '' }}"
                class="w-full px-3 py-2 border rounded">
        </div>

        <div>
            @if($mode === 'edit')
                <label class="block text-sm font-medium mb-1">Country</label>
            @endif
            <select name="country" class="w-full px-3 py-2 border rounded">
                <option value="">{{ $mode === 'create' ? 'Country' : '— None —' }}</option>
                @foreach(\App\Support\Countries::options() as $code => $label)
                    <option value="{{ $code }}" @selected(old('country', $student?->country) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Spoken Language(s)</label>
            @php
                $selectedLanguages = array_values(array_filter(
                    (array) old('languages', $student?->languages ?? []),
                    fn ($c) => \App\Support\Languages::has($c)
                ));
            @endphp
            {{-- Searchable tag picker: type to filter, click to add a removable chip.
                 Inline x-data (not a registered fn) so it also initialises when the
                 create form is toggled in via Livewire. Submits plain languages[]. --}}
            <div x-data="{
                    options: @js(\App\Support\Languages::all()),
                    common: @js(\App\Support\Languages::common()),
                    selected: @js($selectedLanguages),
                    search: '',
                    open: false,
                    available() {
                        const q = this.search.trim().toLowerCase();
                        return Object.keys(this.options)
                            .filter(c => !this.selected.includes(c))
                            .filter(c => q === '' || this.options[c].toLowerCase().includes(q));
                    },
                    groups() {
                        const avail = this.available();
                        if (this.search.trim() !== '') return [{ label: '', items: avail }];
                        return [
                            { label: 'Common', items: this.common.filter(c => avail.includes(c)) },
                            { label: 'All languages', items: avail.filter(c => !this.common.includes(c)) },
                        ];
                    },
                    add(c) { if (!this.selected.includes(c)) this.selected.push(c); this.search = ''; this.$refs.search.focus(); },
                    addFirst() { const a = this.available(); if (a.length) this.add(a[0]); },
                    remove(c) { this.selected = this.selected.filter(x => x !== c); },
                    removeLast() { this.selected.pop(); },
                 }"
                 @click.outside="open = false"
                 class="relative">
                <template x-for="code in selected" :key="code">
                    <input type="hidden" name="languages[]" :value="code">
                </template>

                <div class="flex flex-wrap items-center gap-1.5 w-full px-2 py-1.5 border rounded bg-white focus-within:ring-2 focus-within:ring-blue-200"
                     @click="$refs.search.focus(); open = true">
                    <template x-for="code in selected" :key="code">
                        <span class="inline-flex items-center gap-1 rounded bg-blue-50 text-blue-700 text-sm pl-2 pr-1 py-0.5">
                            <span x-text="options[code]"></span>
                            <button type="button" class="leading-none text-blue-400 hover:text-blue-700" @click.stop="remove(code)">&times;</button>
                        </span>
                    </template>
                    <input x-ref="search" type="text" x-model="search" autocomplete="off"
                           @focus="open = true" @click.stop="open = true"
                           @keydown.backspace="search === '' && removeLast()"
                           @keydown.enter.prevent="addFirst()"
                           @keydown.escape="open = false"
                           :placeholder="selected.length ? '' : 'Type to add a language…'"
                           class="flex-1 min-w-[8rem] border-0 p-0.5 text-sm focus:ring-0 focus:outline-none">
                </div>

                <div x-show="open" x-cloak
                     class="absolute z-20 mt-1 w-full max-h-56 overflow-auto rounded-md border bg-white shadow-lg text-sm">
                    <template x-for="(group, gi) in groups()" :key="gi">
                        <div>
                            <div x-show="group.label && group.items.length"
                                 class="px-3 pt-2 pb-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400"
                                 x-text="group.label"></div>
                            <template x-for="code in group.items" :key="code">
                                <button type="button" class="block w-full text-left px-3 py-1.5 hover:bg-blue-50"
                                        @click="add(code)" x-text="options[code]"></button>
                            </template>
                        </div>
                    </template>
                    <div x-show="available().length === 0" class="px-3 py-2 text-gray-400">No languages to add</div>
                </div>
            </div>
        </div>
    </div>

    <div>
        @if($mode === 'edit')
            <label class="block text-sm font-medium mb-1">Description</label>
        @endif
        <textarea
            name="description"
            rows="{{ $mode === 'create' ? '2' : '3' }}"
            placeholder="{{ $mode === 'create' ? 'Description' : '' }}"
            class="w-full px-3 py-2 border rounded">{{ old('description', $student?->description) }}</textarea>
    </div>
</div>
