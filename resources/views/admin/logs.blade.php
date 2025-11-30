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
                    <tr class="border-t">
                        <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $log->description }}</td>
                        <td class="px-3 py-2 text-gray-700">
                            @if($log->subject)
                                {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-700">
                            @if($log->causer)
                                {{ class_basename($log->causer_type) }} #{{ $log->causer_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-600 text-xs">
                            <pre class="whitespace-pre-wrap">{{ json_encode($log->properties ?? [], JSON_PRETTY_PRINT) }}</pre>
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
