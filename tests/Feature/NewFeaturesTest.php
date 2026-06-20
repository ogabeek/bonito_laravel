<?php

use App\Enums\FeedbackSender;
use App\Models\FeedbackMessage;
use App\Models\FeedbackThread;
use App\Models\Student;
use App\Models\Teacher;
use Livewire\Volt\Volt;

// ─── Absent notes + refund request ───────────────────────────────────────────

it('requires a note when marking a student absent', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    $this->withSession(['teacher_id' => $teacher->id])
        ->postJson(route('teacher.lesson.create'), [
            'student_id' => $student->id,
            'class_date' => now()->format('Y-m-d'),
            'status' => 'student_absent',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['comments']);
});

it('stores a refund request on an absent lesson', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    $this->withSession(['teacher_id' => $teacher->id])
        ->postJson(route('teacher.lesson.create'), [
            'student_id' => $student->id,
            'class_date' => now()->format('Y-m-d'),
            'status' => 'student_absent',
            'comments' => 'No show, reminded twice',
            'refund_requested' => true,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('lessons', [
        'student_id' => $student->id,
        'status' => 'student_absent',
        'refund_requested' => true,
    ]);
});

// ─── Vacation helpers ────────────────────────────────────────────────────────

it('shows a vacation label only for active or upcoming periods', function () {
    $upcoming = Student::factory()->create([
        'vacation_starts_on' => now()->addDays(2),
        'vacation_ends_on' => now()->addDays(9),
    ]);
    $past = Student::factory()->create([
        'vacation_starts_on' => now()->subDays(20),
        'vacation_ends_on' => now()->subDays(13),
    ]);
    $none = Student::factory()->create();

    expect($upcoming->hasPendingVacation())->toBeTrue()
        ->and($upcoming->vacationLabel())->not->toBeNull()
        ->and($past->hasPendingVacation())->toBeFalse()
        ->and($none->vacationLabel())->toBeNull();
});

// ─── Feedback threaded chat ──────────────────────────────────────────────────

it('lets a teacher open a report that the admin can answer', function () {
    $teacher = Teacher::factory()->create();
    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-feedback', ['teacher' => $teacher])
        ->set('newBody', 'The calendar is slow')
        ->call('submitNew')
        ->assertHasNoErrors();

    $thread = FeedbackThread::firstOrFail();
    expect($thread->teacher_id)->toBe($teacher->id)
        ->and($thread->messages)->toHaveCount(1);

    // Teacher's message is awaiting the admin.
    expect(FeedbackMessage::unreadFrom(FeedbackSender::TEACHER)->count())->toBe(1);

    // Admin replies → teacher's message is acknowledged, a new unread admin reply exists.
    session()->forget('teacher_id');
    session(['admin_authenticated' => true]);

    Volt::test('admin-feedback')
        ->set("reply.{$thread->id}", 'Looking into it')
        ->call('submitReply', $thread->id)
        ->assertHasNoErrors();

    expect(FeedbackMessage::unreadFrom(FeedbackSender::TEACHER)->count())->toBe(0)
        ->and(FeedbackMessage::unreadFrom(FeedbackSender::ADMIN)->count())->toBe(1);

    // Teacher opens the panel → the admin reply is marked read (dot clears).
    session()->forget('admin_authenticated');
    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-feedback', ['teacher' => $teacher])->call('openPanel');

    expect(FeedbackMessage::unreadFrom(FeedbackSender::ADMIN)->count())->toBe(0);
});

it('rejects an empty feedback report', function () {
    $teacher = Teacher::factory()->create();
    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-feedback', ['teacher' => $teacher])
        ->set('newBody', '')
        ->call('submitNew')
        ->assertHasErrors('newBody');

    expect(FeedbackThread::count())->toBe(0);
});
