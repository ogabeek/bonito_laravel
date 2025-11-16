@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="p-6">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Admin Dashboard</h1>
            <p class="text-gray-600 text-sm">Manage teachers, students, and assignments</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Teachers</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $stats['teachers'] }}</p>
                    </div>
                    <div class="text-4xl">👨‍🏫</div>
                </div>
                <a href="{{ route('admin.teachers') }}" class="text-blue-600 text-sm hover:underline mt-2 inline-block">Manage →</a>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Students</p>
                        <p class="text-3xl font-bold text-green-600">{{ $stats['students'] }}</p>
                    </div>
                    <div class="text-4xl">👨‍🎓</div>
                </div>
                <a href="{{ route('admin.students') }}" class="text-green-600 text-sm hover:underline mt-2 inline-block">Manage →</a>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Lessons This Month</p>
                        <p class="text-3xl font-bold text-purple-600">{{ $stats['lessons_this_month'] }}</p>
                    </div>
                    <div class="text-4xl">📚</div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">Quick Actions</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('admin.teachers') }}" class="flex items-center gap-3 p-4 border rounded-lg hover:bg-gray-50 transition">
                        <span class="text-2xl">👥</span>
                        <div>
                            <div class="font-semibold text-gray-900">Manage Teachers</div>
                            <div class="text-sm text-gray-600">Add, edit, or remove teachers</div>
                        </div>
                    </a>

                    <a href="{{ route('admin.students') }}" class="flex items-center gap-3 p-4 border rounded-lg hover:bg-gray-50 transition">
                        <span class="text-2xl">🎓</span>
                        <div>
                            <div class="font-semibold text-gray-900">Manage Students</div>
                            <div class="text-sm text-gray-600">Add, edit, or remove students</div>
                        </div>
                    </a>

                    <a href="{{ route('admin.teachers') }}" class="flex items-center gap-3 p-4 border rounded-lg hover:bg-gray-50 transition">
                        <span class="text-2xl">🔗</span>
                        <div>
                            <div class="font-semibold text-gray-900">Assign Students to Teachers</div>
                            <div class="text-sm text-gray-600">Manage teacher-student relationships</div>
                        </div>
                    </a>

                    <a href="{{ route('admin.students.create') }}" class="flex items-center gap-3 p-4 border rounded-lg hover:bg-blue-50 bg-blue-50 transition">
                        <span class="text-2xl">➕</span>
                        <div>
                            <div class="font-semibold text-blue-700">Add New Student</div>
                            <div class="text-sm text-blue-600">Create a new student profile</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
