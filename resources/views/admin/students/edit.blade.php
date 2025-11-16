@extends('layouts.app')

@section('title', 'Edit Student')

@section('content')
<div class="p-6">
    <div class="max-w-2xl mx-auto">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Edit Student</h1>
            <a href="{{ route('admin.students') }}" class="text-gray-600 hover:text-gray-800">← Back</a>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('admin.students.update', $student) }}" class="space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" value="{{ old('name', $student->name) }}" required class="form-input w-full">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Parent Name</label>
                    <input type="text" name="parent_name" value="{{ old('parent_name', $student->parent_name) }}" class="form-input w-full">
                    @error('parent_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $student->email) }}" class="form-input w-full">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Goal</label>
                    <textarea name="goal" rows="2" class="form-input w-full">{{ old('goal', $student->goal) }}</textarea>
                    @error('goal')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input w-full">{{ old('description', $student->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-gray-50 p-3 rounded text-sm">
                    <div class="text-gray-600">Student Portal Link:</div>
                    <a href="{{ route('student.dashboard', $student) }}" target="_blank" class="text-blue-600 hover:underline break-all">
                        {{ route('student.dashboard', $student) }}
                    </a>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="btn-primary">Update Student</button>
                    <a href="{{ route('admin.students') }}" class="px-5 py-2 text-sm font-medium bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Cancel</a>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
