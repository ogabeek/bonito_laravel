<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Services\StudentBalanceService;

beforeEach(function () {
    $this->service = app(StudentBalanceService::class);
});

it('maps paid minus used into class_balance and exposes teacher_ids', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $student->teachers()->attach($teacher);

    $enriched = $this->service
        ->mapBalances(Student::withFullDetails()->get(), [$student->uuid => 10], collect([$student->id => 3]))
        ->firstWhere('id', $student->id);

    expect($enriched->paid_classes)->toBe(10)
        ->and($enriched->used_classes)->toBe(3)
        ->and($enriched->class_balance)->toBe(7)
        ->and($enriched->teacher_ids)->toBe([$teacher->id]);
});

it('leaves paid and balance null when there is no payment data', function () {
    $student = Student::factory()->create();

    $enriched = $this->service
        ->mapBalances(Student::withFullDetails()->get(), [], collect())
        ->firstWhere('id', $student->id);

    expect($enriched->paid_classes)->toBeNull()
        ->and($enriched->used_classes)->toBe(0)
        ->and($enriched->class_balance)->toBeNull();
});

it('leaves paid and balance null when payment data is not numeric', function () {
    $student = Student::factory()->create();

    $enriched = $this->service
        ->mapBalances(Student::withFullDetails()->get(), [$student->uuid => 'check manually'], collect([$student->id => 2]))
        ->firstWhere('id', $student->id);

    expect($enriched->paid_classes)->toBeNull()
        ->and($enriched->used_classes)->toBe(2)
        ->and($enriched->class_balance)->toBeNull();
});

it('allows a negative balance when used exceeds paid', function () {
    $student = Student::factory()->create();

    $enriched = $this->service
        ->mapBalances(Student::withFullDetails()->get(), [$student->uuid => 2], collect([$student->id => 5]))
        ->firstWhere('id', $student->id);

    expect($enriched->class_balance)->toBe(-3);
});
