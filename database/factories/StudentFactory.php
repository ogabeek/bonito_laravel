<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'name' => fake()->name(),
            'parent_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'goal' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => StudentStatus::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::INACTIVE,
        ]);
    }

    public function holiday(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::HOLIDAY,
        ]);
    }
}
