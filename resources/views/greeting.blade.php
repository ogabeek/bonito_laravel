<?php

@extends('layouts.app')

@section('title','Greeting Page')

@section('content')
    <h1>Welcome to Laravel!</h1>
    <p>This is your first view {{$name}}</p>
    <p> YOUR IP is {{$ip}} <p>
@endsection