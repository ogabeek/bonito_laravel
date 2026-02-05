{{--
    * VIEW: Teacher Dashboard (Livewire)
    * The Livewire component handles all data loading and interactions
--}}
@extends('layouts.app', ['favicon' => 'favicon-teacher.svg'])

@section('title', $teacher->name . "'s Dashboard")

@section('content')
    <livewire:teacher-dashboard :teacher="$teacher" />
@endsection
