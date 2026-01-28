@props([
    'types' => ['success', 'error'],
])

@foreach($types as $type)
    @if(session($type))
        @php
            $styles = [
                'success' => 'bg-green-100 border-green-400 text-green-700',
                'error' => 'bg-red-100 border-red-400 text-red-700',
            ];
            $colorClass = $styles[$type] ?? $styles['error'];
        @endphp
        <div {{ $attributes->merge(['class' => "{$colorClass} border px-4 py-3 rounded mb-6"]) }}>
            {{ session($type) }}
        </div>
    @endif
@endforeach
