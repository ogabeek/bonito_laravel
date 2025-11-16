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
Route::prefix('teacher')->name('teacher.')->group(function () {
    // Authentication
    Route::get('/{teacher}', [TeacherController::class, 'showLogin'])->name('login');
    Route::post('/{teacher}/login', [TeacherController::class, 'login'])->name('login.submit');
    Route::post('/logout', [TeacherController::class, 'logout'])->name('logout');
    
    // Dashboard
    Route::get('/{teacher}/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
    
    // Lesson management
    Route::post('/lesson/create', [TeacherController::class, 'createLesson'])->name('lesson.create');
});

// Lesson routes (shared, with model binding)
Route::prefix('lesson')->name('lesson.')->group(function () {
    Route::post('/{lesson}/update', [TeacherController::class, 'updateLesson'])->name('update');
    Route::post('/{lesson}/delete', [TeacherController::class, 'deleteLesson'])->name('delete');
});

// Student routes (UUID-based access)
Route::prefix('student')->name('student.')->group(function () {
    Route::get('/{student}', [\App\Http\Controllers\StudentController::class, 'dashboard'])->name('dashboard');
});
