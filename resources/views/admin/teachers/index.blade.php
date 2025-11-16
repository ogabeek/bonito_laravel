@extends('layouts.app')

@section('title', 'Manage Teachers')

@section('content')
<div class="p-6">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">Teachers</h1>
                <p class="text-gray-600 text-sm">{{ $teachers->count() }} total</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-800">← Back to Dashboard</a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Add New Teacher -->
        <div class="bg-white rounded-lg shadow mb-6" x-data="{ open: false }">
            <button @click="open = !open" class="w-full px-6 py-4 text-left font-semibold text-blue-600 hover:bg-gray-50 flex items-center gap-2">
                <span x-show="!open">▶</span>
                <span x-show="open">▼</span>
                Add New Teacher
            </button>
            
            <div x-show="open" class="px-6 pb-6 border-t" x-cloak>
                <form method="POST" action="{{ route('admin.teachers.create') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" required class="form-input w-full">
                    </div>
                    <div>
                        <label class="form-label">Password *</label>
                        <input type="text" name="password" required class="form-input w-full" placeholder="Min 4 characters">
                    </div>
                    <button type="submit" class="btn-primary">Create Teacher</button>
                </form>
            </div>
        </div>

        <!-- Teachers List -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">All Teachers</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lessons</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($teachers as $teacher)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $teacher->name }}</div>
                                    <div class="text-xs text-gray-500">ID: {{ $teacher->id }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $teacher->students_count }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $teacher->lessons_count }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.teachers.students', $teacher) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Manage Students</a>
                                        <form method="POST" action="{{ route('admin.teachers.delete', $teacher) }}" onsubmit="return confirm('Delete this teacher?')" class="inline">
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
