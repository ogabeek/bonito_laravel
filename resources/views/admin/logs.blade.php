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
                                $status = $log->properties['attributes']['status'] ?? null;
                                
                                // Try to get status from subject's enum if it exists
                                if ($status && $log->subject) {
                                    $statusEnum = null;
                                    if ($log->subject instanceof App\Models\Lesson) {
                                        $statusEnum = App\Enums\LessonStatus::tryFrom($status);
                                    } elseif ($log->subject instanceof App\Models\Student) {
                                        $statusEnum = App\Enums\StudentStatus::tryFrom($status);
                                    }
                                    
                                    if ($statusEnum) {
                                        echo '<span class="inline-flex items-center rounded-full px-2 py-1 ' . $statusEnum->badgeClass() . '">';
                                        echo $statusEnum->label();
                                        echo '</span>';
                                    } else {
                                        echo '<span class="text-gray-400">—</span>';
                                    }
                                } else {
                                    echo '<span class="text-gray-400">—</span>';
                                }
                            @endphp
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
