<?php

use App\Http\Controllers\GreetingController;
use App\Http\Controllers\TeacherController;

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


// Teacher routes
Route::get('/teacher/{teacher}', [TeacherController::class, 'showLogin'])->name('teacher.login');
Route::post('/teacher/{teacher}/login', [TeacherController::class, 'login'])->name('teacher.login.submit');
Route::get('/teacher/{teacher}/dashboard', [TeacherController::class, 'dashboard'])->name('teacher.dashboard');
Route::post('/teacher/logout', [TeacherController::class, 'logout'])->name('teacher.logout');
Route::post('/lesson/{lesson}/update', [TeacherController::class, 'updateLesson'])->name('lesson.update');
Route::post('/teacher/lesson/create', [TeacherController::class, 'createLesson'])->name('lesson.create');
// dd("hello");
