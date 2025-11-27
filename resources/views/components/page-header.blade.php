@props(['title', 'subtitle' => null, 'logoutRoute' => null])

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-gray-700 mt-2 text-sm">{{ $subtitle }}</p>
        @endif
    </div>
    @if($logoutRoute)
        <form method="POST" action="{{ $logoutRoute }}">
            @csrf
            <button class="text-gray-600 hover:text-gray-800">Logout</button>
        </form>
    @endif
</div>
