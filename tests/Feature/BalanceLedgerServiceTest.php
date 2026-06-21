<?php

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\BalanceLedgerService;
use App\Services\BalanceService;
use App\Services\PaymentsService;

beforeEach(function () {
    config(['billing.journal_start' => '2025-12-01']);
    $this->travelTo(Carbon\Carbon::parse('2026-06-22'));
});

/**
 * Resolve a ledger with the two Sheets-backed services faked (paid + payments).
 * LessonRepository stays real, so lesson debits come from the test database.
 *
 * @param  array<int, array{name:string, date:string, hours:float}>  $payments
 */
function ledgerFor(Student $student, int|float|null $paid, array $payments = []): array
{
    test()->mock(BalanceService::class, fn ($m) => $m->shouldReceive('getBalanceForUuid')->andReturn($paid));
    test()->mock(PaymentsService::class, fn ($m) => $m->shouldReceive('forStudent')->andReturn(collect($payments)));

    return app(BalanceLedgerService::class)->forStudent($student);
}

it('ends at paid minus used and reconciles', function () {
    $student = Student::factory()->create();
    $teacher = Teacher::factory()->create();

    Lesson::factory()->for($student)->for($teacher)->completed()->create(['class_date' => '2026-01-10']);
    Lesson::factory()->for($student)->for($teacher)->completed()->create(['class_date' => '2026-01-12']);
    Lesson::factory()->for($student)->for($teacher)->studentCancelled()->create(['class_date' => '2026-01-15']);
    Lesson::factory()->for($student)->for($teacher)->studentAbsent()->create(['class_date' => '2026-01-20']);
    Lesson::factory()->for($student)->for($teacher)->completed()->create(['class_date' => '2026-02-01']);

    $data = ledgerFor($student, paid: 10, payments: [
        ['name' => $student->name, 'date' => '2026-01-18', 'hours' => 4.0],
    ]);

    expect($data['paid'])->toBe(10.0)
        ->and($data['used'])->toBe(4)              // 3 completed + 1 absent (cancelled excluded)
        ->and($data['payments_total'])->toBe(4.0)
        ->and($data['opening'])->toBe(6.0)         // 10 paid - 4 payments
        ->and($data['current_balance'])->toBe(6.0) // 10 paid - 4 used
        ->and($data['computed_end'])->toBe($data['current_balance']) // the reconciliation invariant
        ->and($data['has_balance_data'])->toBeTrue();
});

it('orders a same-day payment before the lesson it funds', function () {
    $student = Student::factory()->create();
    $teacher = Teacher::factory()->create();
    Lesson::factory()->for($student)->for($teacher)->completed()->create(['class_date' => '2026-03-03']);

    $data = ledgerFor($student, paid: 5, payments: [
        ['name' => $student->name, 'date' => '2026-03-03', 'hours' => 5.0],
    ]);

    // opening = 5 - 5 = 0; payment lands first (0 -> 5), then the lesson (5 -> 4)
    $entries = $data['entries'];
    expect($entries[0]['type'])->toBe('payment')
        ->and($entries[0]['balance'])->toBe(5.0)
        ->and($entries[1]['type'])->toBe('lesson')
        ->and($entries[1]['balance'])->toBe(4.0);
});

it('absorbs lessons before the cutoff and ignores future lessons', function () {
    $student = Student::factory()->create();
    $teacher = Teacher::factory()->create();
    Lesson::factory()->for($student)->for($teacher)->completed()->create(['class_date' => '2025-11-01']); // pre-cutoff
    Lesson::factory()->for($student)->for($teacher)->completed()->create(['class_date' => '2026-12-01']); // future
    Lesson::factory()->for($student)->for($teacher)->completed()->create(['class_date' => '2026-01-05']); // in window

    $data = ledgerFor($student, paid: 5);

    expect($data['used'])->toBe(1)
        ->and($data['entries'])->toHaveCount(1)
        ->and($data['computed_end'])->toBe(4.0);
});

it('produces a negative opening when journal payments exceed paid', function () {
    $student = Student::factory()->create();

    $data = ledgerFor($student, paid: 26, payments: [
        ['name' => $student->name, 'date' => '2025-12-10', 'hours' => 10.0],
        ['name' => $student->name, 'date' => '2026-03-03', 'hours' => 20.0],
    ]);

    expect($data['opening'])->toBe(-4.0)            // 26 paid - 30 journal payments
        ->and($data['current_balance'])->toBe(26.0) // no lessons -> balance = paid
        ->and($data['computed_end'])->toBe(26.0);
});

it('flags students with no balance data', function () {
    $student = Student::factory()->create();

    $data = ledgerFor($student, paid: null);

    expect($data['has_balance_data'])->toBeFalse()
        ->and($data['opening'])->toBeNull()
        ->and($data['current_balance'])->toBeNull();
});

it('renders the balance ledger on the admin student edit page', function () {
    $student = Student::factory()->create(['name' => 'Ledger Student']);
    $teacher = Teacher::factory()->create();
    Lesson::factory()->for($student)->for($teacher)->completed()->create(['class_date' => '2026-01-10']);

    test()->mock(BalanceService::class, fn ($m) => $m->shouldReceive('getBalanceForUuid')->andReturn(10));
    test()->mock(PaymentsService::class, fn ($m) => $m->shouldReceive('forStudent')->andReturn(collect()));

    $this->withSession(['admin_authenticated' => true])
        ->get(route('admin.students.edit', $student))
        ->assertOk()
        ->assertSee('Balance ledger')
        ->assertSee('Balance now');
});
