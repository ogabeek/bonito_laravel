@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Edit Teacher')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Teacher</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">← Back</a>
    </div>

    @php $teacherUrl = url("/teacher/{$teacher->id}"); @endphp
    <div class="flex items-center gap-2 mb-6 text-sm" x-data="{ copied: false }">
        <span class="text-gray-500">Teacher link:</span>
        <a href="{{ $teacherUrl }}" class="text-blue-600 hover:underline" target="_blank">{{ $teacherUrl }}</a>
        <button type="button"
                @click="navigator.clipboard.writeText('{{ $teacherUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="px-2 py-0.5 text-xs border rounded hover:bg-gray-50"
                x-text="copied ? 'Copied!' : 'Copy'">
            Copy
        </button>
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
