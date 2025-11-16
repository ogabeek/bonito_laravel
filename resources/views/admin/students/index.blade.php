@extends('layouts.app')

@section('title', 'Manage Students')

@section('content')
<div class="p-6">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">Students</h1>
                <p class="text-gray-600 text-sm">{{ $students->count() }} total</p>
            </div>
            <div class="flex gap-4">
                <a href="{{ route('admin.students.create') }}" class="btn-primary">+ Add New Student</a>
                <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-800">← Back</a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Students List -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">All Students</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teachers</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lessons</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($students as $student)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $student->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        <a href="{{ route('student.dashboard', $student) }}" target="_blank" class="text-blue-600 hover:underline">
                                            View Portal →
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $student->parent_name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $student->teachers_count }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $student->lessons_count }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.students.edit', $student) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                                        <form method="POST" action="{{ route('admin.students.delete', $student) }}" onsubmit="return confirm('Delete this student and all their lessons?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
