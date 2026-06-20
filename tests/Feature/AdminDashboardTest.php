<?php

use App\Enums\StudentStatus;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

beforeEach(function () {
    config(['app.admin_password' => Hash::make('test-password')]);
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

it('hides and shows students by status via the calendar toggle', function () {
    Student::factory()->create(['name' => 'Active Annie']);
    Student::factory()->create(['name' => 'Dropped Dan', 'status' => StudentStatus::DROPPED]);

    Volt::test('admin-dashboard')
        ->assertSee('Active Annie')
        ->assertSee('Dropped Dan')
        ->call('toggleStatus', StudentStatus::DROPPED->value)
        ->assertSet('hiddenStatuses', [StudentStatus::DROPPED->value])
        ->assertSee('Active Annie')
        ->assertDontSee('Dropped Dan')
        ->call('toggleStatus', StudentStatus::DROPPED->value)
        ->assertSet('hiddenStatuses', [])
        ->assertSee('Dropped Dan');
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

it('updates a teacher and keeps the password when left blank', function () {
    $teacher = Teacher::factory()->create(['password' => 'original-pin']);

    $this->withSession(['admin_authenticated' => true])
        ->put(route('admin.teachers.update', $teacher), [
            'name' => 'Renamed Teacher',
            'password' => '',
            'zoom_link' => 'https://zoom.us/j/123',
        ])
        ->assertRedirect(route('admin.teachers.edit', $teacher))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('teachers', [
        'id' => $teacher->id,
        'name' => 'Renamed Teacher',
        'password' => 'original-pin', // unchanged
        'zoom_link' => 'https://zoom.us/j/123',
    ]);
});

it('updates a teacher password when provided', function () {
    $teacher = Teacher::factory()->create(['password' => 'original-pin']);

    $this->withSession(['admin_authenticated' => true])
        ->put(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'password' => 'new-pin',
        ])
        ->assertRedirect(route('admin.teachers.edit', $teacher));

    $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'password' => 'new-pin']);
});

it('rejects a teacher update with a missing name', function () {
    $teacher = Teacher::factory()->create();

    $this->withSession(['admin_authenticated' => true])
        ->put(route('admin.teachers.update', $teacher), ['name' => ''])
        ->assertSessionHasErrors('name');
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
