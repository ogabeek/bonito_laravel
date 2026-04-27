@props(['title', 'subtitle' => null, 'logoutRoute' => null])

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-gray-500 sm:text-gray-600 mt-1.5 sm:mt-2 text-sm leading-snug">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="flex items-center gap-4">
        {{ $slot }}
        @if($logoutRoute)
            <form method="POST" action="{{ $logoutRoute }}">
                @csrf
                <button class="text-gray-600 hover:text-gray-800">Logout</button>
            </form>
        @endif
    </div>
</div>
