@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Edit Teacher')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Teacher</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">← Back</a>
    </div>

    <x-session-alert />

    <x-card>
        <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}">
            @csrf
            @method('PUT')
            <x-teacher-form :teacher="$teacher" mode="edit" />
            <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Update Teacher
            </button>
        </form>
    </x-card>
</div>
@endsection
