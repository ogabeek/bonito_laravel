@props(['teacher' => null, 'mode' => 'create'])

<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            @if($mode === 'edit')
                <label class="block text-sm font-medium mb-1">Name *</label>
            @endif
            <input
                type="text"
                name="name"
                value="{{ old('name', $teacher?->name) }}"
                placeholder="{{ $mode === 'create' ? 'Name *' : '' }}"
                required
                class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
            >
            @error('name')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        @if($mode === 'create')
            <div>
                <input
                    type="text"
                    name="password"
                    placeholder="PIN *"
                    required
                    class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                >
                @error('password')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        @else
            <div>
                <label class="block text-sm font-medium mb-1">PIN</label>
                <input
                    type="text"
                    name="password"
                    value="{{ old('password', $teacher?->password) }}"
                    placeholder="Leave blank to keep current"
                    class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                >
                @error('password')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Contact</label>
                <input
                    type="text"
                    name="contact"
                    value="{{ old('contact', $teacher?->contact) }}"
                    placeholder="email or phone"
                    class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                >
                @error('contact')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>

    @if($mode === 'edit')
        <hr class="my-2">
        <p class="text-sm font-medium text-gray-700">Zoom</p>
        <div>
            <label class="block text-sm font-medium mb-1">Link</label>
            <input
                type="url"
                name="zoom_link"
                value="{{ old('zoom_link', $teacher?->zoom_link) }}"
                placeholder="https://us02web.zoom.us/j/..."
                class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
            >
            @error('zoom_link')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Meeting ID</label>
                <input
                    type="text"
                    name="zoom_id"
                    value="{{ old('zoom_id', $teacher?->zoom_id) }}"
                    placeholder="123 456 7890"
                    class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Passcode</label>
                <input
                    type="text"
                    name="zoom_passcode"
                    value="{{ old('zoom_passcode', $teacher?->zoom_passcode) }}"
                    placeholder="1234"
                    class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                >
            </div>
        </div>
    @endif
</div>
