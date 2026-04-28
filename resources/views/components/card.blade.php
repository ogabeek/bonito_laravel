@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-sm']) }}>
    @if($title)
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-700">{{ $title }}</h2>
        </div>
    @endif
    <div class="p-4 sm:p-6">
        {{ $slot }}
    </div>
</div>
