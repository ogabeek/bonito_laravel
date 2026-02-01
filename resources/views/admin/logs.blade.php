@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Activity Logs')

@section('content')
<div class="p-6 max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Activity Logs</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">← Back</a>
    </div>

    <x-card>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">When</th>
                    <th class="px-3 py-2 text-left">Action</th>
                    <th class="px-3 py-2 text-left">Subject</th>
                    <th class="px-3 py-2 text-left">Causer</th>
                    <th class="px-3 py-2 text-left">Props</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-600 whitespace-nowrap">
                            {{ $log->created_at->format('M d, H:i') }}
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-900">
                            {{ $log->description }}
                        </td>
                        <td class="px-3 py-2">
                            @if($log->subject)
                                @php
                                    $name = match(class_basename($log->subject)) {
                                        'Student' => $log->subject->name,
                                        'Teacher' => $log->subject->name,
                                        'Lesson' => "Lesson #{$log->subject->id}",
                                        default => class_basename($log->subject) . " #{$log->subject->id}",
                                    };
                                    $color = match(class_basename($log->subject)) {
                                        'Student' => 'text-blue-700',
                                        'Teacher' => 'text-purple-700',
                                        default => 'text-gray-700',
                                    };
                                @endphp
                                <span class="font-medium {{ $color }}">{{ $name }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($log->causer)
                                <span class="font-medium text-gray-700">{{ $log->causer->name }}</span>
                            @else
                                <span class="text-gray-400">System</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs">
                            @php 
                                $props = collect($log->properties ?? [])
                                    ->except(['action', 'student_id'])
                                    ->sortBy(fn($v, $k) => match($k) {
                                        'student_name', 'name' => 1,
                                        'class_date' => 2,
                                        'status' => 3,
                                        'topic' => 4,
                                        'comments' => 5,
                                        'homework' => 6,
                                        default => 99,
                                    });
                                $subjectType = $log->subject_type ?? null;
                            @endphp
                            
                            @if($props->isNotEmpty())
                                <div class="text-gray-600 space-y-0.5">
                                    @foreach($props as $key => $value)
                                        @if(is_string($value) || is_numeric($value))
                                            <div>
                                                <span class="font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                
                                                @if($key === 'status')
                                                    @php
                                                        $statusEnum = null;
                                                        if ($log->subject instanceof App\Models\Lesson) {
                                                            $statusEnum = App\Enums\LessonStatus::tryFrom($value);
                                                        } elseif ($log->subject instanceof App\Models\Student) {
                                                            $statusEnum = App\Enums\StudentStatus::tryFrom($value);
                                                        } elseif ($subjectType && str_contains($subjectType, 'Lesson')) {
                                                            $statusEnum = App\Enums\LessonStatus::tryFrom($value);
                                                        } elseif ($subjectType && str_contains($subjectType, 'Student')) {
                                                            $statusEnum = App\Enums\StudentStatus::tryFrom($value);
                                                        }
                                                    @endphp
                                                    
                                                    @if($statusEnum)
                                                        <span class="font-semibold {{ str_replace(['bg-', '100'], ['text-', '700'], $statusEnum->badgeClass()) }}">
                                                            {{ $statusEnum->label() }}
                                                        </span>
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                @elseif($key === 'class_date')
                                                    {{ \Carbon\Carbon::parse($value)->format('M d, Y') }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-4 text-center text-gray-500">No activity yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</div>
@endsection
