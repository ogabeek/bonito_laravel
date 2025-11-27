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
