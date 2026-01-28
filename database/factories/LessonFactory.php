<?php

namespace Database\Factories;

use App\Enums\LessonStatus;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'student_id' => Student::factory(),
            'class_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'status' => LessonStatus::COMPLETED,
            'topic' => fake()->sentence(),
            'homework' => fake()->sentence(),
            'comments' => fake()->paragraph(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonStatus::COMPLETED,
        ]);
    }

    public function studentAbsent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonStatus::STUDENT_ABSENT,
        ]);
    }

    public function studentCancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonStatus::STUDENT_CANCELLED,
        ]);
    }

    public function teacherCancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonStatus::TEACHER_CANCELLED,
        ]);
    }

    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'class_date' => now(),
        ]);
    }
}
