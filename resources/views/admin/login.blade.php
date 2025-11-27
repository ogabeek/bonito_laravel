@extends('layouts.app', ['favicon' => 'favicon-admin.svg'])

@section('title', 'Admin Login')

@section('content')
<x-login-card 
    title="Admin Login" 
    subtitle="School Management System"
    :action="route('admin.login.submit')"
/>
@endsection
