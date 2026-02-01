<?php

/**
 * * ROUTES: Web routes for the application
 *
 * Route Groups:
 * - /           → Redirects to admin login
 * - /teacher/*  → Teacher login & dashboard (session auth)
 * - /lesson/*   → Lesson CRUD (teacher auth required)
 * - /student/*  → Student dashboard (UUID-based, no login)
 * - /admin/*    → Admin dashboard & management
 */

use App\Http\Controllers\TeacherController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Return a fresh CSRF token (used by JS to refresh tokens for long-lived pages)
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});
// * TEACHER ROUTES: /teacher/{slug}/...
Route::prefix('teacher')->name('teacher.')->group(function () {
    // Public: login page and form submit
    Route::get('/{teacher}', [TeacherController::class, 'showLogin'])->name('login');
    Route::post('/{teacher}/login', [TeacherController::class, 'login'])
        ->middleware('throttle:5,1')  // ! Rate limit: 5 attempts per minute
        ->name('login.submit');
    Route::post('/logout', [TeacherController::class, 'logout'])->name('logout');

    // Protected: requires teacher.auth middleware
    Route::middleware('teacher.auth')->group(function () {
        Route::get('/{teacher}/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
        Route::post('/lesson/create', [TeacherController::class, 'createLesson'])->name('lesson.create');
    });
});

// * LESSON ROUTES: /lesson/{id}
Route::middleware('teacher.auth')
    ->prefix('lesson')
    ->name('lesson.')
    ->group(function () {
        Route::put('/{lesson}', [TeacherController::class, 'updateLesson'])->name('update');
        Route::delete('/{lesson}', [TeacherController::class, 'deleteLesson'])->name('delete');
    });

// * STUDENT ROUTES: /student/{uuid}
// ? No auth required - UUID acts as access token
Route::prefix('student')->name('student.')->group(function () {
    Route::get('/{student}', [\App\Http\Controllers\StudentController::class, 'dashboard'])->name('dashboard');
});

// * ADMIN ROUTES: /admin/...
Route::prefix('admin')->name('admin.')->group(function () {
    // Public: login
    Route::get('/login', [\App\Http\Controllers\AdminController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AdminController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
    Route::post('/logout', [\App\Http\Controllers\AdminController::class, 'logout'])->name('logout');

    // Protected: requires admin.auth middleware
    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');

        // Teachers CRUD
        Route::post('/teachers', [\App\Http\Controllers\AdminController::class, 'createTeacher'])->name('teachers.create');
        Route::delete('/teachers/{teacher}', [\App\Http\Controllers\AdminController::class, 'deleteTeacher'])->name('teachers.delete');
        Route::post('/teachers/{teacher}/restore', [\App\Http\Controllers\AdminController::class, 'restoreTeacher'])->name('teachers.restore');

        // Students CRUD
        Route::post('/students', [\App\Http\Controllers\AdminController::class, 'createStudent'])->name('students.store');
        Route::get('/students/{student}/edit', [\App\Http\Controllers\AdminController::class, 'editStudentForm'])->name('students.edit');
        Route::put('/students/{student}', [\App\Http\Controllers\AdminController::class, 'updateStudent'])->name('students.update');
        Route::post('/students/{student}/status', [\App\Http\Controllers\AdminController::class, 'updateStudentStatus'])->name('students.status.update');
        Route::post('/students/{student}/assign-teacher', [\App\Http\Controllers\AdminController::class, 'assignTeacherToStudent'])->name('student.assign.teacher');
        Route::delete('/students/{student}/teachers/{teacher}', [\App\Http\Controllers\AdminController::class, 'unassignStudent'])->name('teachers.students.unassign');

        // Activity log
        Route::get('/logs', [\App\Http\Controllers\AdminController::class, 'logs'])->name('logs');

        // Billing & stats
        Route::get('/billing', [\App\Http\Controllers\AdminController::class, 'billing'])->name('billing');
        Route::post('/billing/export', [\App\Http\Controllers\AdminController::class, 'exportBilling'])->name('billing.export');
        Route::post('/billing/refresh', [\App\Http\Controllers\AdminController::class, 'refreshBalance'])->name('billing.refresh');
    });
});
