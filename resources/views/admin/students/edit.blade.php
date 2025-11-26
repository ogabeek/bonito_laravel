@extends('layouts.app')

@section('title', 'Edit Student')

@section('content')
<div class="p-6">
    <div class="max-w-3xl mx-auto">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">Edit Student</h1>
                <p class="text-gray-600 text-sm">Update student information</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-800">← Back to Dashboard</a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Edit Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="POST" action="{{ route('admin.students.update', $student) }}" class="space-y-4">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Student Name *</label>
                        <input type="text" name="name" value="{{ old('name', $student->name) }}" required class="form-input w-full">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="form-label">Parent Name</label>
                        <input type="text" name="parent_name" value="{{ old('parent_name', $student->parent_name) }}" class="form-input w-full">
                        @error('parent_name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $student->email) }}" class="form-input w-full">
                        @error('email')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="form-label">Goal</label>
                        <input type="text" name="goal" value="{{ old('goal', $student->goal) }}" class="form-input w-full">
                        @error('goal')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input w-full">{{ old('description', $student->description) }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">UUID (Read-only)</label>
                    <input type="text" value="{{ $student->uuid }}" readonly class="form-input w-full bg-gray-100">
                    <p class="text-xs text-gray-500 mt-1">Student portal: <a href="{{ route('student.dashboard', $student) }}" target="_blank" class="text-blue-600 hover:underline">{{ route('student.dashboard', $student) }}</a></p>
                </div>
                
                <div class="flex justify-between items-center pt-4">
                    <button type="submit" class="btn-primary">Update Student</button>
                </div>
            </form>
        </div>

        <!-- Teacher Assignment -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Assign Teachers</h2>
            
            <!-- Assigned Teachers -->
            @if($student->teachers->count() > 0)
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">Currently Assigned:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($student->teachers as $teacher)
                            <div class="bg-blue-100 text-blue-700 px-3 py-2 rounded flex items-center gap-2">
                                <span>{{ $teacher->name }}</span>
                                <form method="POST" action="{{ route('admin.teachers.students.unassign', [$teacher, $student]) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-bold">×</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Assign New Teacher -->
            <form method="POST" action="{{ route('admin.student.assign.teacher', $student) }}" class="flex gap-2">
                @csrf
                <select name="teacher_id" required class="form-input flex-1">
                    <option value="">Select teacher to assign...</option>
                    @php
                        $assignedTeacherIds = $student->teachers->pluck('id')->toArray();
                        $availableTeachers = \App\Models\Teacher::whereNotIn('id', $assignedTeacherIds)->get();
                    @endphp
                    @foreach($availableTeachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary">Assign Teacher</button>
            </form>
        </div>

        <!-- Student Info & Statistics -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Statistics & Links</h2>
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Student Portal:</span>
                    <a href="{{ route('student.dashboard', $student) }}" target="_blank" class="text-blue-600 hover:underline font-medium">
                        View Portal →
                    </a>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Total Lessons:</span>
                    <span class="font-medium">{{ $student->lessons()->count() }}</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Completed Lessons:</span>
                    <span class="font-medium text-green-600">{{ $student->lessons()->where('status', 'completed')->count() }}</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Student Absent:</span>
                    <span class="font-medium text-yellow-600">{{ $student->lessons()->where('status', 'student_absent')->count() }}</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Teacher Cancelled:</span>
                    <span class="font-medium text-red-600">{{ $student->lessons()->where('status', 'teacher_cancelled')->count() }}</span>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-red-800 mb-2">Danger Zone</h2>
            <p class="text-sm text-red-700 mb-4">Deleting this student will remove all their lessons and assignments. This action cannot be undone.</p>
            
            <form method="POST" action="{{ route('admin.students.delete', $student) }}" onsubmit="return confirm('Are you sure you want to delete {{ $student->name }}? This will delete all their lessons and cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded">
                    Delete Student
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
