@extends('layouts.app')

@section('title', 'Server Error')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="text-center">
        <div class="text-6xl font-bold text-gray-300 mb-4">500</div>
        <h1 class="text-2xl font-semibold text-gray-800 mb-2">Something Went Wrong</h1>
        <p class="text-gray-600 mb-6">We're working on fixing this. Please try again later.</p>
        <a href="{{ url('/') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Go Home
        </a>
    </div>
</div>
@endsection
