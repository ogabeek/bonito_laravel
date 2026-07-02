@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="text-center">
        <div class="text-6xl font-bold text-gray-300 mb-4">404</div>
        <h1 class="text-2xl font-semibold text-gray-800 mb-2">Page Not Found</h1>
        <p class="text-gray-600 mb-6">The page you're looking for doesn't exist or has been moved.</p>
        <x-button href="{{ url('/') }}">Go Home</x-button>
    </div>
</div>
@endsection
