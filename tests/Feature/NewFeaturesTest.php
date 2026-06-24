<?php

use App\Enums\FeedbackSender;
use App\Enums\FeedbackStatus;
use App\Enums\StudentStatus;
use App\Models\FeedbackMessage;
use App\Models\FeedbackThread;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Livewire\Volt\Volt;
use Spatie\Activitylog\Models\Activity;

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

it('stores and logs the follow-up choices for an absent lesson', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);

    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-dashboard', ['teacher' => $teacher])
        ->set('student_id', (string) $student->id)
        ->set('class_date', now()->format('Y-m-d'))
        ->set('status', 'student_absent')
        ->set('comments', 'No show, reminded twice')
        ->set('absence_reminder_sent', true)
        ->set('absence_chat_notified', true)
        ->set('refund_requested', true)
        ->call('createLesson')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('lessons', [
        'student_id' => $student->id,
        'status' => 'student_absent',
        'absence_reminder_sent' => true,
        'absence_chat_notified' => true,
        'refund_requested' => true,
    ]);

    $activity = Activity::query()->where('description', 'lesson_created')->latest()->firstOrFail();

    expect($activity->properties->get('needs_recovery'))->toBeTrue()
        ->and($activity->properties->get('reminder_sent'))->toBeTrue()
        ->and($activity->properties->get('no_response'))->toBeTrue();

    $this->withSession(['admin_authenticated' => true])
        ->get(route('admin.logs'))
        ->assertSuccessful()
        ->assertSee('Student absent')
        ->assertSee('Needs recovery')
        ->assertSee('Reminder sent')
        ->assertSee('No response');
});

it('shows concise lesson details and follow-up choices in the calendar tooltip', function () {
    $teacher = Teacher::factory()->create(['name' => 'Maria Teacher']);
    $student = Student::factory()->create();
    $lesson = Lesson::factory()->studentAbsent()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'comments' => 'Student did not join the call.',
        'absence_reminder_sent' => true,
        'absence_chat_notified' => false,
        'refund_requested' => true,
    ])->load('teacher');

    $html = Blade::render('<x-calendar-lesson-chip :lesson="$lesson" />', ['lesson' => $lesson]);

    expect($html)
        ->toContain('Maria Teacher')
        ->toContain('Notes:')
        ->toContain('Student did not join the call.')
        ->toContain('Needs recovery')
        ->toContain('Reminder sent')
        ->toContain('No response')
        ->not->toContain('Date:')
        ->not->toContain('Student Absent')
        ->not->toContain('Refund requested');
});

it('uses neutral status cards in the teacher lesson history', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);
    Lesson::factory()->studentAbsent()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now(),
        'comments' => 'Student did not attend.',
        'refund_requested' => true,
    ]);
    session(['teacher_id' => $teacher->id]);

    $html = Volt::test('teacher-dashboard', ['teacher' => $teacher])->html();

    expect($html)
        ->toContain('--lesson-card-bg: #f9fafb')
        ->toContain('border-red-200 bg-red-50 text-red-700')
        ->toContain('Student did not attend.')
        ->toContain('Needs recovery')
        ->toContain('Lessons')
        ->not->toContain('📚 Lessons');
});

// ─── Vacation helpers ────────────────────────────────────────────────────────

it('shows a vacation label only for active or upcoming periods', function () {
    $upcoming = Student::factory()->create([
        'status' => StudentStatus::HOLIDAY,
        'vacation_starts_on' => now()->addDays(2),
        'vacation_ends_on' => now()->addDays(9),
    ]);
    $past = Student::factory()->create([
        'status' => StudentStatus::HOLIDAY,
        'vacation_starts_on' => now()->subDays(20),
        'vacation_ends_on' => now()->subDays(13),
    ]);
    $none = Student::factory()->create();

    expect($upcoming->hasPendingVacation())->toBeTrue()
        ->and($upcoming->vacationLabel())->not->toBeNull()
        ->and($past->hasPendingVacation())->toBeFalse()
        ->and($none->vacationLabel())->toBeNull();
});

it('marks vacation days on the admin calendar', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-01'));

    try {
        $student = Student::factory()->holiday()->create([
            'name' => 'Vacation Student',
            'vacation_starts_on' => '2026-07-10',
            'vacation_ends_on' => '2026-07-12',
        ]);

        $html = Volt::test('admin-dashboard')->html();

        expect($html)
            ->toContain($student->name)
            ->toContain('bg-violet-50/70')
            ->not->toContain('ring-violet-200')
            ->not->toContain('🏖')
            ->and(substr_count($html, 'title="On vacation: Jul 10 – Jul 12"'))->toBe(3);
    } finally {
        Carbon::setTestNow();
    }
});

it('keeps the student list compact and shows status choices inside the editor', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create(['name' => 'Quiet Student']);
    $teacher->students()->attach($student);
    Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'class_date' => now()->subDays(8),
    ]);
    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-dashboard', ['teacher' => $teacher])
        ->assertSee('Change status')
        ->assertSee('Active')
        ->assertSee('Inactive')
        ->assertSee('On Holiday')
        ->assertSee('Finished')
        ->assertSee('Dropped')
        ->assertSee('no class in 7 days — still active?')
        ->assertDontSee('Review status')
        ->assertDontSee('Student statuses are editable');
});

it('lets a teacher change an assigned student status', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);
    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-dashboard', ['teacher' => $teacher])
        ->call('saveStudentStatus', $student->id, 'holiday', '2026-07-10', '2026-07-20', 'Family trip')
        ->assertHasNoErrors();

    $student->refresh();

    expect($student->status->value)->toBe('holiday')
        ->and($student->vacation_starts_on->toDateString())->toBe('2026-07-10')
        ->and($student->vacation_ends_on->toDateString())->toBe('2026-07-20')
        ->and($student->status_note)->toBe('Family trip');
});

it('clears a stale success message when a later submit fails validation', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);
    session(['teacher_id' => $teacher->id]);

    $component = Volt::test('teacher-dashboard', ['teacher' => $teacher])
        ->set('student_id', (string) $student->id)
        ->set('class_date', now()->format('Y-m-d'))
        ->set('status', 'completed')
        ->set('topic', 'Present perfect')
        ->call('createLesson')
        ->assertHasNoErrors();

    expect($component->get('showSuccess'))->toBeTrue();

    // An invalid follow-up (absent with no note) must show the error, not the stale success.
    $component->set('status', 'student_absent')
        ->set('comments', '')
        ->call('createLesson')
        ->assertHasErrors('comments');

    expect($component->get('showSuccess'))->toBeFalse();
});

// ─── Feedback threaded chat ──────────────────────────────────────────────────

it('invites teachers to share platform feedback without emoji copy', function () {
    $teacher = Teacher::factory()->create();
    session(['teacher_id' => $teacher->id]);

    $component = Volt::test('teacher-feedback', ['teacher' => $teacher]);

    $component->assertSee('Help improve this space')
        ->assertDontSee('💬 Feedback')
        ->call('togglePanel')
        ->assertSee('Share your experience')
        ->assertSee('This platform is under development')
        ->assertSee('we read every message');
});

it('lets a teacher open a report that the admin can answer', function () {
    $teacher = Teacher::factory()->create();
    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-feedback', ['teacher' => $teacher])
        ->set('body', 'The calendar is slow')
        ->call('send')
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

    Volt::test('teacher-feedback', ['teacher' => $teacher])->call('togglePanel');

    expect(FeedbackMessage::unreadFrom(FeedbackSender::ADMIN)->count())->toBe(0);
});

it('lets the admin resolve a thread and a teacher reply reopens it', function () {
    $teacher = Teacher::factory()->create();
    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-feedback', ['teacher' => $teacher])
        ->set('body', 'Need help')
        ->call('send');

    $thread = FeedbackThread::firstOrFail();

    // Admin resolves → status flips and the teacher's message is acknowledged.
    session()->forget('teacher_id');
    session(['admin_authenticated' => true]);

    Volt::test('admin-feedback')->call('resolve', $thread->id);

    expect($thread->fresh()->status)->toBe(FeedbackStatus::RESOLVED)
        ->and(FeedbackMessage::unreadFrom(FeedbackSender::TEACHER)->count())->toBe(0);

    // A new teacher reply reopens the thread.
    session()->forget('admin_authenticated');
    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-feedback', ['teacher' => $teacher])
        ->set('body', 'Still broken')
        ->call('send');

    expect($thread->fresh()->status)->toBe(FeedbackStatus::OPEN);
});

it('posts as admin when an admin uses the widget on a teacher dashboard', function () {
    $teacher = Teacher::factory()->create();

    // Teacher opens the conversation.
    session(['teacher_id' => $teacher->id]);
    Volt::test('teacher-feedback', ['teacher' => $teacher])->set('body', 'Help please')->call('send');

    // Admin previews the same dashboard and replies from the widget.
    session()->forget('teacher_id');
    session(['admin_authenticated' => true]);
    Volt::test('teacher-feedback', ['teacher' => $teacher])
        ->call('togglePanel')
        ->set('body', 'On it')
        ->call('send')
        ->assertHasNoErrors();

    $thread = FeedbackThread::firstOrFail();
    expect($thread->messages()->where('sender', FeedbackSender::ADMIN)->count())->toBe(1)
        // Admin opening the widget acknowledged the teacher's message.
        ->and(FeedbackMessage::unreadFrom(FeedbackSender::TEACHER)->count())->toBe(0);
});

it('rejects an empty feedback report', function () {
    $teacher = Teacher::factory()->create();
    session(['teacher_id' => $teacher->id]);

    Volt::test('teacher-feedback', ['teacher' => $teacher])
        ->set('body', '')
        ->call('send')
        ->assertHasErrors('body');

    expect(FeedbackThread::count())->toBe(0);
});
