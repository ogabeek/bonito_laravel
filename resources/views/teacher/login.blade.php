@extends('layouts.app')

@section('title', 'Teacher Login')

@section('content')
<x-login-card 
    title="Teacher Login" 
    :action="route('teacher.login.submit', $teacher->id)"
>
    <p class="text-gray-600 mb-4">Hello, {{ $teacher->name }}</p>
</x-login-card>
@endsection