@extends('layouts.app')

@section('title', 'Teacher Login')

@section('content')
<div class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h1 class="text-2xl font-bold mb-6">Teacher Login</h1>
        <p class="text-gray-600 mb-4">Hello, {{ $teacher->name }}</p>
        
        <form method="POST" action="{{ route('teacher.login.submit', $teacher->id) }}">
            @csrf
            
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
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
@endsection