@extends('layouts.app')

@section('title', 'Manage ' . $teacher->name . "'s Students")

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">{{ $teacher->name }}'s Students</h1>
                <p class="text-gray-600 text-sm">Manage which students are assigned to this teacher</p>
            </div>
            <a href="{{ route('admin.teachers') }}" class="text-gray-600 hover:text-gray-800">← Back to Teachers</a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Assigned Students -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b bg-green-50">
                    <h2 class="text-xl font-semibold text-green-700">✓ Assigned Students ({{ $assignedStudents->count() }})</h2>
                </div>
                <div class="p-6">
                    @if($assignedStudents->count() > 0)
                        <div class="space-y-2">
                            @foreach($assignedStudents as $student)
                                <div class="flex justify-between items-center p-3 border rounded hover:bg-gray-50">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $student->name }}</div>
                                        @if($student->parent_name)
                                            <div class="text-xs text-gray-500">Parent: {{ $student->parent_name }}</div>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('admin.teachers.students.unassign', [$teacher, $student]) }}" onsubmit="return confirm('Unassign this student?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Remove</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No students assigned yet</p>
                    @endif
                </div>
            </div>

            <!-- Available Students -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b bg-blue-50">
                    <h2 class="text-xl font-semibold text-blue-700">+ Available Students ({{ $availableStudents->count() }})</h2>
                </div>
                <div class="p-6">
                    @if($availableStudents->count() > 0)
                        <div class="space-y-2">
                            @foreach($availableStudents as $student)
                                <div class="flex justify-between items-center p-3 border rounded hover:bg-gray-50">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $student->name }}</div>
                                        @if($student->parent_name)
                                            <div class="text-xs text-gray-500">Parent: {{ $student->parent_name }}</div>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('admin.teachers.students.assign', $teacher) }}">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Assign</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">All students are already assigned</p>
                    @endif
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
