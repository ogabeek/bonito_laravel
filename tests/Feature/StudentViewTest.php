<?php

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;

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

it('shows teacher name once as page-level teacher info', function () {
    $teacher = Teacher::factory()->create(['name' => 'Test Teacher Name']);
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    Lesson::factory()->count(2)->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->subDay(),
    ]);

    $response = $this->get(route('student.dashboard', $student));

    $response->assertSuccessful();

    expect(substr_count(strip_tags($response->getContent()), 'Test Teacher Name'))->toBe(1);
});

it('shows neutral lesson status badges for non-completed lessons', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    Lesson::factory()->studentAbsent()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->subDays(3),
    ]);

    Lesson::factory()->studentCancelled()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->subDays(2),
    ]);

    Lesson::factory()->teacherCancelled()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->subDay(),
    ]);

    $this->get(route('student.dashboard', $student))
        ->assertSuccessful()
        ->assertSee('Missing')
        ->assertSee('Canceled by student')
        ->assertSee('Canceled by teacher');
});

it('shows teacher materials as a parent-facing button', function () {
    $student = Student::factory()->create([
        'teacher_notes' => 'Practice before next class.',
        'materials_url' => 'https://example.com/materials',
    ]);

    $this->get(route('student.dashboard', $student))
        ->assertSuccessful()
        ->assertSee('From teacher')
        ->assertSee('Practice before next class.')
        ->assertSee('Class materials')
        ->assertSee('https://example.com/materials');
});

it('shows weekly class distribution for the selected year', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 15));

    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => '2026-01-01',
    ]);

    Lesson::factory()->studentAbsent()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => '2026-01-02',
    ]);

    Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => '2026-02-01',
    ]);

    Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => '2025-01-01',
    ]);

    try {
        $this->get(route('student.dashboard', $student))
            ->assertSuccessful()
            ->assertSee('2026')
            ->assertSee('Jan 1 - Jan 7: 2 classes');
    } finally {
        Carbon::setTestNow();
    }
});
