@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow']) }}>
    @if($title)
        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold text-gray-700">{{ $title }}</h2>
        </div>
    @endif
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
