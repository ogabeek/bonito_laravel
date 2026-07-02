<?php

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;

it('requires admin auth for the billing page', function () {
    $this->get(route('admin.billing'))->assertRedirect(route('admin.login'));
});

it('renders the billing page with charts and export controls', function () {
    $teacher = Teacher::factory()->create();
    $student = Student::factory()->create();
    $teacher->students()->attach($student);
    Lesson::factory()->create(['teacher_id' => $teacher->id, 'student_id' => $student->id]);

    $this->withSession(['admin_authenticated' => true])
        ->get(route('admin.billing'))
        ->assertOk()
        ->assertSee('Export to Sheet')
        ->assertSee('Refresh Balance')
        ->assertSee('billing-chart-data', false);
});
