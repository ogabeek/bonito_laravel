<?php

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;

it('shows student dashboard via uuid', function () {
    $student = Student::factory()->create();

    $this->get(route('student.dashboard', $student))
        ->assertSuccessful()
        ->assertSee($student->name);
});

it('shows 404 for invalid uuid', function () {
    $this->get(route('student.dashboard', ['student' => 'invalid-uuid']))
        ->assertNotFound();
});

it('displays upcoming lessons', function () {
    $teacher = Teacher::factory()->create(['name' => 'Future Teacher']);
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    $upcomingLesson = Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->addDays(5),
    ]);

    $this->get(route('student.dashboard', $student))
        ->assertSuccessful()
        ->assertSee('Upcoming Lessons')
        ->assertSee('Future Teacher');
});

it('displays past lessons grouped by month', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    $pastLesson = Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->subDays(10),
        'topic' => 'Past Lesson Topic',
    ]);

    $this->get(route('student.dashboard', $student))
        ->assertSuccessful()
        ->assertSee('Past Lesson Topic');
});

it('shows lesson statistics', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    Lesson::factory()->count(5)->completed()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->subDays(5),
    ]);

    Lesson::factory()->count(2)->studentAbsent()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->subDays(3),
    ]);

    $this->get(route('student.dashboard', $student))
        ->assertSuccessful();
});

it('shows empty state when no lessons', function () {
    $student = Student::factory()->create();

    $this->get(route('student.dashboard', $student))
        ->assertSuccessful();
});

it('displays teacher name on lessons', function () {
    $teacher = Teacher::factory()->create(['name' => 'Test Teacher Name']);
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->subDay(),
    ]);

    $this->get(route('student.dashboard', $student))
        ->assertSuccessful()
        ->assertSee('Test Teacher Name');
});
