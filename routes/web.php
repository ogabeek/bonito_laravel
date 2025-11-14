<?php

use App\Http\Controllers\GreetingController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hello', function(){
    return 'Hello world! 🪴';
});

Route::get('/greet/{name}', function($name){
    return "Hello, $name! 🪴";
});


Route::get('/profile/{name}/{ip}', [GreetingController::class, 'show']);



// dd("hello");
