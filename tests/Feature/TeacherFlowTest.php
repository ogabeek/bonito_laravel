<?php

use App\Enums\LessonStatus;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

it('shows teacher login form', function () {
    $teacher = Teacher::factory()->create();

    $this->get(route('teacher.login', $teacher))
        ->assertSuccessful()
        ->assertSee($teacher->name);
});

it('authenticates teacher with valid password', function () {
    $teacher = Teacher::factory()->create([
        'password' => 'teacher-password',
    ]);

    $this->post(route('teacher.login.submit', $teacher), ['password' => 'teacher-password'])
        ->assertRedirect(route('teacher.dashboard', $teacher));

    $this->assertEquals($teacher->id, session('teacher_id'));
});

it('authenticates teacher with old hashed pin', function () {
    $teacherId = DB::table('teachers')->insertGetId([
        'name' => 'Legacy Teacher',
        'password' => bcrypt('legacy-pin'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $teacher = Teacher::findOrFail($teacherId);

    $this->post(route('teacher.login.submit', $teacher), ['password' => 'legacy-pin'])
        ->assertRedirect(route('teacher.dashboard', $teacher));

    $this->assertEquals($teacher->id, session('teacher_id'));
});

it('rejects teacher with invalid password', function () {
    $teacher = Teacher::factory()->create([
        'password' => 'correct-password',
    ]);

    $this->post(route('teacher.login.submit', $teacher), ['password' => 'wrong-password'])
        ->assertRedirect()
        ->assertSessionHasErrors('password');

    $this->assertNull(session('teacher_id'));
});

it('shows teacher dashboard with their students', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    Lesson::factory()->count(3)->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now(),
    ]);

    $this->withSession(['teacher_id' => $teacher->id])
        ->get(route('teacher.dashboard', $teacher))
        ->assertSuccessful()
        ->assertSee($student->name);
});

it('redirects unauthenticated teacher to their login page', function () {
    $teacher = Teacher::factory()->create();

    $this->get(route('teacher.dashboard', $teacher))
        ->assertRedirect(route('teacher.login', $teacher));
});

it('prevents teacher from accessing another teacher dashboard', function () {
    $teacher1 = Teacher::factory()->create();
    $teacher2 = Teacher::factory()->create();

    $this->withSession(['teacher_id' => $teacher1->id])
        ->get(route('teacher.dashboard', $teacher2))
        ->assertRedirect(route('teacher.login', $teacher2));
});

it('creates a lesson for assigned student', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-dashboard', ['teacher' => $teacher])
        ->set('student_id', (string) $student->id)
        ->set('class_date', now()->format('Y-m-d'))
        ->set('status', LessonStatus::COMPLETED->value)
        ->set('topic', 'Test Topic')
        ->set('homework', 'Test Homework')
        ->set('comments', 'Test Comments')
        ->call('createLesson')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('lessons', [
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'topic' => 'Test Topic',
    ]);
});

it('prevents creating lesson for unassigned student', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();

    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-dashboard', ['teacher' => $teacher])
        ->set('student_id', (string) $student->id)
        ->set('class_date', now()->format('Y-m-d'))
        ->set('status', LessonStatus::COMPLETED->value)
        ->set('topic', 'Test Topic')
        ->call('createLesson')
        ->assertHasErrors(['student_id']);

    $this->assertDatabaseCount('lessons', 0);
});

it('prevents creating lesson for archived student', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);
    $student->delete();

    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-dashboard', ['teacher' => $teacher])
        ->set('student_id', (string) $student->id)
        ->set('class_date', now()->format('Y-m-d'))
        ->set('status', LessonStatus::COMPLETED->value)
        ->set('topic', 'Test Topic')
        ->call('createLesson')
        ->assertHasErrors(['student_id']);

    $this->assertDatabaseCount('lessons', 0);
});

it('ignores absence follow-up flags when creating a non-absent lesson', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-dashboard', ['teacher' => $teacher])
        ->set('student_id', (string) $student->id)
        ->set('class_date', now()->format('Y-m-d'))
        ->set('status', LessonStatus::COMPLETED->value)
        ->set('topic', 'Back to class')
        ->set('absence_reminder_sent', true)
        ->set('absence_chat_notified', true)
        ->set('refund_requested', true)
        ->call('createLesson')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('lessons', [
        'student_id' => $student->id,
        'absence_reminder_sent' => false,
        'absence_chat_notified' => false,
        'refund_requested' => false,
    ]);
});

it('deletes own lesson', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    $lesson = Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
    ]);

    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-dashboard', ['teacher' => $teacher])
        ->call('deleteLesson', $lesson->id);

    $this->assertSoftDeleted('lessons', ['id' => $lesson->id]);
});

it('prevents deleting another teacher lesson', function () {
    $teacher1 = Teacher::factory()->create();
    $teacher2 = Teacher::factory()->create();
    $student = Student::factory()->create();

    $lesson = Lesson::factory()->create([
        'teacher_id' => $teacher2->id,
        'student_id' => $student->id,
    ]);

    session(['teacher_id' => $teacher1->id]);

    Volt::test('teacher-dashboard', ['teacher' => $teacher1])
        ->call('deleteLesson', $lesson->id);

    $this->assertNotSoftDeleted('lessons', ['id' => $lesson->id]);
});

it('logs out teacher', function () {
    $teacher = Teacher::factory()->create();

    $this->withSession(['teacher_id' => $teacher->id])
        ->post(route('teacher.logout'))
        ->assertRedirect('/');

    $this->assertNull(session('teacher_id'));
});
