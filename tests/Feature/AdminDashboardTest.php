<?php

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;

beforeEach(function () {
    config(['app.admin_password' => 'test-password']);
});

it('redirects unauthenticated users to login', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));
});

it('shows login form', function () {
    $this->get(route('admin.login'))
        ->assertSuccessful()
        ->assertSee('Admin');
});

it('authenticates with valid password', function () {
    $this->post(route('admin.login.submit'), ['password' => 'test-password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertTrue(session('admin_authenticated'));
});

it('rejects invalid password', function () {
    $this->post(route('admin.login.submit'), ['password' => 'wrong-password'])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertNull(session('admin_authenticated'));
});

it('loads dashboard with lessons data', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    Lesson::factory()->count(3)->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now(),
    ]);

    $this->withSession(['admin_authenticated' => true])
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee($teacher->name)
        ->assertSee($student->name);
});

it('loads billing page with stats', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    Lesson::factory()->count(5)->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now(),
    ]);

    $this->withSession(['admin_authenticated' => true])
        ->get(route('admin.billing'))
        ->assertSuccessful();
});

it('navigates between months on dashboard', function () {
    $this->withSession(['admin_authenticated' => true])
        ->get(route('admin.dashboard', ['year' => 2025, 'month' => 6]))
        ->assertSuccessful();
});

it('creates a new teacher', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.teachers.create'), [
            'name' => 'New Teacher',
            'password' => 'secret',
        ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('teachers', ['name' => 'New Teacher']);
});

it('creates a new student', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.students.store'), [
            'name' => 'New Student',
            'parent_name' => 'Parent Name',
            'email' => 'student@example.com',
        ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('students', ['name' => 'New Student']);
});

it('archives (soft deletes) a teacher', function () {
    $teacher = Teacher::factory()->create();

    $this->withSession(['admin_authenticated' => true])
        ->delete(route('admin.teachers.delete', $teacher))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
});

it('restores an archived teacher', function () {
    $teacher = Teacher::factory()->create();
    $teacher->delete();

    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.teachers.restore', $teacher->id))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'deleted_at' => null]);
});

it('assigns a teacher to a student', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();

    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.student.assign.teacher', $student), [
            'teacher_id' => $teacher->id,
        ])
        ->assertRedirect();

    $this->assertTrue($student->teachers->contains($teacher));
});

it('logs out admin user', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    $this->assertNull(session('admin_authenticated'));
});
