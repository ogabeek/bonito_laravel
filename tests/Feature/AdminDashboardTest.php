<?php

use App\Enums\StudentStatus;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Spatie\Activitylog\Models\Activity;

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
        ->assertSee('Admin')
        ->assertDontSee('cdn.tailwindcss.com', false)
        ->assertDontSee(asset('css/app.css'), false);
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
        ->assertSee('admin_hidden_student_statuses')
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

    $teacher = Teacher::where('name', 'New Teacher')->firstOrFail();

    expect(Hash::check('secret', $teacher->password))->toBeTrue();
});

it('updates a teacher and keeps the password when left blank', function () {
    $teacher = Teacher::factory()->create(['password' => 'original-pin']);
    $originalPassword = $teacher->getRawOriginal('password');

    $this->withSession(['admin_authenticated' => true])
        ->put(route('admin.teachers.update', $teacher), [
            'name' => 'Renamed Teacher',
            'password' => '',
            'zoom_link' => 'https://zoom.us/j/123',
        ])
        ->assertRedirect(route('admin.teachers.edit', $teacher))
        ->assertSessionHas('success');

    $teacher->refresh();

    expect($teacher->name)->toBe('Renamed Teacher')
        ->and($teacher->zoom_link)->toBe('https://zoom.us/j/123')
        ->and($teacher->getRawOriginal('password'))->toBe($originalPassword);
});

it('updates a teacher password when provided', function () {
    $teacher = Teacher::factory()->create(['password' => 'original-pin']);

    $this->withSession(['admin_authenticated' => true])
        ->put(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'password' => 'new-pin',
        ])
        ->assertRedirect(route('admin.teachers.edit', $teacher));

    expect(Hash::check('new-pin', $teacher->refresh()->password))->toBeTrue()
        ->and($teacher->password)->not->toBe('new-pin');
});

it('hashes legacy teacher pins and removes them from historical activity properties', function () {
    $teacherId = DB::table('teachers')->insertGetId([
        'name' => 'Legacy Teacher',
        'password' => 'legacy-pin',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $teacher = Teacher::findOrFail($teacherId);

    activity()
        ->performedOn($teacher)
        ->withProperties([
            'changes' => ['password' => 'legacy-pin'],
            'original' => ['password' => 'older-pin'],
        ])
        ->log('teacher_updated');

    $migration = require database_path('migrations/2026_06_21_203710_hash_existing_teacher_passwords.php');
    $migration->up();

    $storedPassword = DB::table('teachers')->where('id', $teacherId)->value('password');
    $properties = Activity::latest('id')->firstOrFail()->properties->toArray();

    expect(Hash::check('legacy-pin', $storedPassword))->toBeTrue()
        ->and(data_get($properties, 'changes.password'))->toBeNull()
        ->and(data_get($properties, 'original.password'))->toBeNull();
});

it('clears vacation metadata when admin changes a student status', function () {
    $student = Student::factory()->holiday()->create([
        'vacation_starts_on' => '2026-07-10',
        'vacation_ends_on' => '2026-07-20',
        'status_note' => 'Family trip',
    ]);

    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.students.status.update', $student), [
            'status' => StudentStatus::FINISHED->value,
        ])
        ->assertRedirect();

    $student->refresh();

    expect($student->status)->toBe(StudentStatus::FINISHED)
        ->and($student->vacation_starts_on)->toBeNull()
        ->and($student->vacation_ends_on)->toBeNull()
        ->and($student->status_note)->toBeNull();
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
