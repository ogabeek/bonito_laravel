@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Edit Student')

@section('content')
<div class="p-6 max-w-3xl mx-auto">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Student</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">← Back</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <x-card class="mb-6">
        <form method="POST" action="{{ route('admin.students.update', $student) }}">
            @csrf
            @method('PUT')
            <x-student-form :student="$student" mode="edit" />
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 mt-4">Update Student</button>
        </form>
    </x-card>

    <x-card title="Teachers" class="mb-6">
        @if($student->teachers->count() > 0)
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($student->teachers as $teacher)
                    <div class="bg-gray-100 px-3 py-1 rounded flex items-center gap-2">
                        <span>{{ $teacher->name }}</span>
                        <form method="POST" action="{{ route('admin.teachers.students.unassign', [$student, $teacher]) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">×</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('admin.student.assign.teacher', $student) }}" class="flex gap-2">
            @csrf
            <select name="teacher_id" required class="flex-1 px-3 py-2 border rounded">
                <option value="">Select teacher...</option>
                @foreach($availableTeachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Assign</button>
        </form>
    </x-card>

    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <form method="POST" action="{{ route('admin.students.delete', $student) }}" onsubmit="return confirm('Delete {{ $student->name }}? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete Student</button>
        </form>
    </div>
</div>
@endsection
