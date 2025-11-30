<?php

use App\Http\Controllers\TeacherController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Teacher routes
Route::prefix('teacher')->name('teacher.')->group(function () {
    // Authentication
    Route::get('/{teacher}', [TeacherController::class, 'showLogin'])->name('login');
    Route::post('/{teacher}/login', [TeacherController::class, 'login'])->name('login.submit');
    Route::post('/logout', [TeacherController::class, 'logout'])->name('logout');

    // Protected routes (require teacher authentication)
    Route::middleware('teacher.auth')->group(function () {
        Route::get('/{teacher}/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
        Route::post('/lesson/create', [TeacherController::class, 'createLesson'])->name('lesson.create');
    });
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

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Auth routes (public)
    Route::get('/login', [\App\Http\Controllers\AdminController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AdminController::class, 'login'])->name('login.submit');
    Route::post('/logout', [\App\Http\Controllers\AdminController::class, 'logout'])->name('logout');
    
    // Protected routes (require session)
    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
        
        // Teachers
        Route::post('/teachers', [\App\Http\Controllers\AdminController::class, 'createTeacher'])->name('teachers.create');
        Route::delete('/teachers/{teacher}', [\App\Http\Controllers\AdminController::class, 'deleteTeacher'])->name('teachers.delete');
        Route::post('/teachers/{id}/restore', [\App\Http\Controllers\AdminController::class, 'restoreTeacher'])->name('teachers.restore');

        // Students
        Route::post('/students', [\App\Http\Controllers\AdminController::class, 'createStudent'])->name('students.store');
        Route::get('/students/{student}/edit', [\App\Http\Controllers\AdminController::class, 'editStudentForm'])->name('students.edit');
        Route::put('/students/{student}', [\App\Http\Controllers\AdminController::class, 'updateStudent'])->name('students.update');
        Route::post('/students/{student}/status', [\App\Http\Controllers\AdminController::class, 'updateStudentStatus'])->name('students.status.update');
        Route::post('/students/{student}/assign-teacher', [\App\Http\Controllers\AdminController::class, 'assignTeacherToStudent'])->name('student.assign.teacher');
        Route::delete('/students/{student}/teachers/{teacher}', [\App\Http\Controllers\AdminController::class, 'unassignStudent'])->name('teachers.students.unassign');
    });
});
