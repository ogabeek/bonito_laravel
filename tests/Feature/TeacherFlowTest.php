<?php

use App\Enums\LessonStatus;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;

it('shows teacher login form', function () {
    $teacher = Teacher::factory()->create();

    $this->get(route('teacher.login', $teacher))
        ->assertSuccessful()
        ->assertSee($teacher->name);
});

it('authenticates teacher with valid password', function () {
    $teacher = Teacher::factory()->create([
        'password' => bcrypt('teacher-password'),
    ]);

    $this->post(route('teacher.login.submit', $teacher), ['password' => 'teacher-password'])
        ->assertRedirect(route('teacher.dashboard', $teacher));

    $this->assertEquals($teacher->id, session('teacher_id'));
});

it('rejects teacher with invalid password', function () {
    $teacher = Teacher::factory()->create([
        'password' => bcrypt('correct-password'),
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

it('redirects unauthenticated teacher to home', function () {
    $teacher = Teacher::factory()->create();

    $this->get(route('teacher.dashboard', $teacher))
        ->assertRedirect('/');
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

    $lessonData = [
        'student_id' => $student->id,
        'class_date' => now()->format('Y-m-d'),
        'status' => LessonStatus::COMPLETED->value,
        'topic' => 'Test Topic',
        'homework' => 'Test Homework',
        'comments' => 'Test Comments',
    ];

    $this->withSession(['teacher_id' => $teacher->id])
        ->postJson(route('teacher.lesson.create'), $lessonData)
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('lessons', [
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'topic' => 'Test Topic',
    ]);
});

it('prevents creating lesson for unassigned student', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();

    $this->withSession(['teacher_id' => $teacher->id])
        ->postJson(route('teacher.lesson.create'), [
            'student_id' => $student->id,
            'class_date' => now()->format('Y-m-d'),
            'status' => LessonStatus::COMPLETED->value,
            'topic' => 'Test Topic',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['student_id']);
});

it('updates own lesson', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    $lesson = Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
    ]);

    $this->withSession(['teacher_id' => $teacher->id])
        ->postJson(route('lesson.update', $lesson), [
            'status' => LessonStatus::STUDENT_ABSENT->value,
            'topic' => 'Updated Topic',
            'homework' => 'Updated Homework',
            'comments' => 'Updated Comments',
        ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('lessons', [
        'id' => $lesson->id,
        'topic' => 'Updated Topic',
        'status' => LessonStatus::STUDENT_ABSENT->value,
    ]);
});

it('prevents updating another teacher lesson', function () {
    $teacher1 = Teacher::factory()->create();
    $teacher2 = Teacher::factory()->create();
    $student = Student::factory()->create();

    $lesson = Lesson::factory()->create([
        'teacher_id' => $teacher2->id,
        'student_id' => $student->id,
    ]);

    $this->withSession(['teacher_id' => $teacher1->id])
        ->postJson(route('lesson.update', $lesson), [
            'status' => LessonStatus::COMPLETED->value,
        ])
        ->assertForbidden();
});

it('deletes own lesson', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    $lesson = Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
    ]);

    $this->withSession(['teacher_id' => $teacher->id])
        ->postJson(route('lesson.delete', $lesson))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

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

    $this->withSession(['teacher_id' => $teacher1->id])
        ->postJson(route('lesson.delete', $lesson))
        ->assertForbidden();
});

it('logs out teacher', function () {
    $teacher = Teacher::factory()->create();

    $this->withSession(['teacher_id' => $teacher->id])
        ->post(route('teacher.logout'))
        ->assertRedirect('/');

    $this->assertNull(session('teacher_id'));
});
