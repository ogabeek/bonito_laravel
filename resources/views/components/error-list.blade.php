@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'mb-4 bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm px-3 sm:px-4 py-2 sm:py-3 rounded']) }}>
        <div class="font-semibold mb-1">Please fix the following:</div>
        <ul class="list-disc ml-4 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
