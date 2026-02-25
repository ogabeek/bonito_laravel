@props([
    'title',
    'subtitle' => null,
    'action',
    'inputLabel' => 'Password',
])

<div class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h1 class="text-2xl font-bold {{ $subtitle ? 'mb-2' : 'mb-6' }}">{{ $title }}</h1>
        
        @if($subtitle)
            <p class="text-gray-600 mb-6 text-sm">{{ $subtitle }}</p>
        @endif
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif
        
        <form method="POST" action="{{ $action }}">
            @csrf
            
            {{-- Optional slot for extra fields (like greeting) --}}
            {{ $slot }}
            
            <div class="mb-4">
                <label for="password" class="form-label">{{ $inputLabel }}</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="form-input w-full"
                    required
                    autofocus
                >
                
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <button type="submit" class="btn-primary w-full">Login</button>
        </form>
    </div>
</div>
