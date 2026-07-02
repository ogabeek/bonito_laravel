@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Edit Student')

@section('content')
<div class="p-6 max-w-3xl mx-auto">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Student</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">← Back</a>
    </div>

    <x-session-alert />

    <x-student-ledger :ledger="$ledger" />

    <x-card class="mb-6">
        <form method="POST" action="{{ route('admin.students.update', $student) }}">
            @csrf
            @method('PUT')
            <x-student-form :student="$student" mode="edit" />
            <x-button type="submit" class="mt-4">Update Student</x-button>
        </form>
    </x-card>

    <x-card title="Teachers" class="mb-6">
        @if($student->teachers->count() > 0)
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($student->teachers as $teacher)
                    <div class="bg-gray-100 px-3 py-1 rounded flex items-center gap-2">
                        <span>{{ $teacher->name }}</span>
                        <form method="POST" action="{{ route('admin.teachers.students.unassign', [$student, $teacher]) }}" class="inline"
                              x-data="{ armed: false }"
                              @submit.prevent="if (armed) $el.submit(); else armed = true"
                              @click.outside="armed = false">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-red-600 hover:text-red-800"
                                    :class="armed && 'font-bold underline'"
                                    aria-label="Remove {{ $teacher->name }} from this student"
                                    x-text="armed ? 'Remove?' : '×'">
                            </button>
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
            <x-button type="submit">Assign</x-button>
        </form>
    </x-card>

    <x-card title="Archive Student">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-600">
                Archive hides this student from active calendars, teacher lists, and the public student page. Lessons and history stay preserved.
            </p>
            <form method="POST" action="{{ route('admin.students.delete', $student) }}"
                  x-data="{ armed: false }"
                  @submit.prevent="if (armed) $el.submit(); else armed = true"
                  @click.outside="armed = false">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="rounded px-4 py-2 text-sm font-medium"
                        :class="armed ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-700 hover:bg-orange-100'"
                        x-text="armed ? 'Confirm archive' : 'Archive student'">
                </button>
            </form>
        </div>
    </x-card>
</div>
@endsection
