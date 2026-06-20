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

use App\Http\Controllers\Admin;
use App\Http\Controllers\TeacherController;

Route::get('/', function () {
    return redirect()->route('admin.login');
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
    Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

    // Protected: requires admin.auth middleware
    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/logs', [Admin\DashboardController::class, 'logs'])->name('logs');

        // Teachers CRUD
        Route::post('/teachers', [Admin\TeacherController::class, 'store'])->name('teachers.create');
        Route::get('/teachers/{teacher}/edit', [Admin\TeacherController::class, 'edit'])->name('teachers.edit');
        Route::put('/teachers/{teacher}', [Admin\TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('/teachers/{teacher}', [Admin\TeacherController::class, 'destroy'])->name('teachers.delete');
        Route::post('/teachers/{teacher}/restore', [Admin\TeacherController::class, 'restore'])->name('teachers.restore');

        // Students CRUD
        Route::post('/students', [Admin\StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}/edit', [Admin\StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [Admin\StudentController::class, 'update'])->name('students.update');
        Route::post('/students/{student}/status', [Admin\StudentController::class, 'updateStatus'])->name('students.status.update');
        Route::post('/students/{student}/assign-teacher', [Admin\StudentController::class, 'assignTeacher'])->name('student.assign.teacher');
        Route::delete('/students/{student}/teachers/{teacher}', [Admin\StudentController::class, 'unassign'])->name('teachers.students.unassign');

        // Billing & stats
        Route::get('/billing', [Admin\BillingController::class, 'index'])->name('billing');
        Route::post('/billing/export', [Admin\BillingController::class, 'export'])->name('billing.export');
        Route::post('/billing/refresh', [Admin\BillingController::class, 'refresh'])->name('billing.refresh');
    });
});
