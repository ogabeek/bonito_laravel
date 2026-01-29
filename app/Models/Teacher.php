<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ! MODEL: Teacher - Represents a tutor who conducts lessons
 *
 * * Teachers log into the system with their password
 * * They can only see and manage their own students and lessons
 * * Teachers can be "soft deleted" (archived) when they leave
 *
 * * Database Table Structure (teachers):
 * * ┌──────────────┬─────────────────┬──────────────────────────────────┐
 * * │ Column       │ Type            │ Description                      │
 * * ├──────────────┼─────────────────┼──────────────────────────────────┤
 * * │ id           │ integer (PK)    │ Primary key                      │
 * * │ name         │ string          │ Teacher's display name           │
 * * │ password     │ string          │ Hashed password for login        │
 * * │ created_at   │ timestamp       │ Auto-managed                     │
 * * │ updated_at   │ timestamp       │ Auto-managed                     │
 * * │ deleted_at   │ timestamp       │ Soft delete (null = active)      │
 * * └──────────────┴─────────────────┴──────────────────────────────────┘
 *
 * ? Why no email field?
 * ? This is a simple app - teachers access via direct link: /teacher/{id}
 * ? Authentication is password-only (no username/email required)
 */
class Teacher extends Model
{
    /**
     * * HasFactory: Enables Teacher::factory() for testing
     * * SoftDeletes: Archived teachers keep their lesson history
     *
     * ! Important: SoftDeletes means:
     * ! Teacher::all() → Only active teachers
     * ! Teacher::withTrashed()->get() → Includes archived
     * ! Teacher::onlyTrashed()->get() → Only archived
     */
    use HasFactory, SoftDeletes;

    /**
     * ! MASS ASSIGNMENT - Only these fields can be set via create/update
     *
     * ! Security Note: 'password' is fillable but should ALWAYS be hashed
     * ! Never store plain text passwords!
     * ! Use: Hash::make($password) before saving
     */
    protected $fillable = [
        'name',      // * Teacher's display name
        'password',  // * Hashed password (bcrypt)
    ];

    /**
     * ! RELATIONSHIP: Teacher has many Lessons
     *
     * * A teacher conducts many lessons over time
     * * Database: lessons.teacher_id → teachers.id
     *
     * * Usage: $teacher->lessons              → All lessons by this teacher
     * * Usage: $teacher->lessons()->forMonth($date)->get()  → Monthly lessons
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * ! RELATIONSHIP: Teacher has many Students (Many-to-Many)
     *
     * * Same as Student->teachers() but from the other side
     * * Uses the same pivot table: student_teacher
     *
     * * Usage: $teacher->students                     → All assigned students
     * * Usage: $teacher->students()->attach($id)      → Assign a student
     * * Usage: $teacher->students()->orderBy('name')  → Alphabetical list
     *
     * ? Why many-to-many?
     * ? A student might have different teachers for different subjects
     * ? A teacher can tutor multiple students
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    /**
     * ! QUERY SCOPE: Eager load related data
     *
     * * Loads counts and relationships in a single query
     * * Prevents N+1 problems when displaying teacher lists
     *
     * * Usage: Teacher::withFullDetails()->get()
     * * Result: Each teacher has students_count, lessons_count, and students loaded
     */
    public function scopeWithFullDetails($query)
    {
        return $query->withCount('students', 'lessons')->with('students');
    }
}
