<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hello', function(){
    return 'Hello world! 🪴';
});

Route::get('/greet/{name}', function($name){
    return "Hello, $name! 🪴";
});


Route::get('/greeting', function(){
    return view('greeting');
});

Route::get('/profile/{name}/{ip}', function($name, $ip){
    return view('greeting', ['name'=> $name, 'ip' => $ip]);
});



// dd("hello");
