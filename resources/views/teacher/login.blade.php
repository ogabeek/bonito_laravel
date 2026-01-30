@extends('layouts.app', ['favicon' => 'favicon-teacher.svg'])

@section('title', 'Teacher Login')

@section('content')

@php
    $now = now();
    $maintenanceStart = \Carbon\Carbon::parse('2026-01-30 00:00:00');
    $maintenanceEnd = \Carbon\Carbon::parse('2026-02-01 00:00:00');
    $isMaintenanceMode = $now->between($maintenanceStart, $maintenanceEnd);
@endphp

@if($isMaintenanceMode)
    <div class="max-w-md mx-auto mb-6">
        <x-info-banner type="warning" icon=" ⚠️🛠️ ">
            <p class="mt-1 text-sm">
                We're currently showcasing the platform. 
                Normal operations resume on <strong>February 1st, 2026</strong>.
            </p>
            <span class="text-xs opacity-75 mt-2 block">
                Contact if anything on Telegram: 
                <a href="https://t.me/ogabeeek" target="_blank" rel="noopener" 
                   class="underline hover:opacity-80 font-medium">
                    @ogabeeek 
                </a>
            </span>
        </x-info-banner>
    </div>
@endif

<x-login-card 
    title="Teacher Login" 
    :action="route('teacher.login.submit', $teacher->id)"
>
    <p class="text-gray-600 mb-4">Hello, {{ $teacher->name }}</p>
</x-login-card>
@endsection