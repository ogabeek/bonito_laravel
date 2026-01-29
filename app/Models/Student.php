<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * ! MODEL: Student - Represents a tutoring student
 *
 * * Students are the people receiving tutoring lessons.
 * * Each student can have MULTIPLE teachers (many-to-many relationship)
 * * Students have a public UUID for sharing their progress page
 *
 * * Database Table Structure (students):
 * * ┌──────────────┬─────────────────┬────────────────────────────────────┐
 * * │ Column       │ Type            │ Description                        │
 * * ├──────────────┼─────────────────┼────────────────────────────────────┤
 * * │ id           │ integer (PK)    │ Internal ID (never exposed)        │
 * * │ uuid         │ string (unique) │ Public ID for URLs (e.g., abc123)  │
 * * │ name         │ string          │ Student's full name                │
 * * │ parent_name  │ string          │ Parent/guardian name               │
 * * │ email        │ string          │ Contact email                      │
 * * │ goal         │ string          │ Learning goals                     │
 * * │ description  │ text            │ Additional notes                   │
 * * │ status       │ string (enum)   │ active/inactive/holiday            │
 * * │ created_at   │ timestamp       │ Auto-managed                       │
 * * │ updated_at   │ timestamp       │ Auto-managed                       │
 * * └──────────────┴─────────────────┴────────────────────────────────────┘
 *
 * * Pivot Table (student_teacher) - Many-to-Many relationship:
 * * ┌──────────────┬──────────────┐
 * * │ student_id   │ teacher_id   │
 * * ├──────────────┼──────────────┤
 * * │ 1            │ 1            │  ← Student 1 assigned to Teacher 1
 * * │ 1            │ 2            │  ← Student 1 also assigned to Teacher 2
 * * │ 2            │ 1            │  ← Student 2 assigned to Teacher 1
 * * └──────────────┴──────────────┘
 */
class Student extends Model
{
    use HasFactory;

    /**
     * ! MASS ASSIGNMENT - Allowed fields for create/update
     */
    protected $fillable = [
        'uuid',         // * Unique public identifier (auto-generated)
        'name',         // * Student's name
        'parent_name',  // * Parent/guardian contact
        'email',        // * Email for notifications
        'goal',         // * Learning objectives
        'description',  // * Additional notes about the student
        'status',       // * active, inactive, holiday
    ];

    /**
     * ! DEFAULT ATTRIBUTE VALUES
     *
     * * When creating a new Student, these values are set automatically
     * * unless explicitly provided
     */
    protected $attributes = [
        'status' => 'active',   // * New students are active by default
    ];

    /**
     * ! ATTRIBUTE CASTING
     *
     * * Converts status string → StudentStatus enum
     * * Example: $student->status->label() → "Active"
     */
    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
        ];
    }

    /**
     * ! MODEL BOOT METHOD - Lifecycle hooks
     *
     * * boot() runs when the model class is first used
     * * We use it to register "event listeners"
     *
     * ? What is static::creating()?
     * ? It's a callback that runs BEFORE a new model is saved to database
     * ? Perfect for auto-generating values like UUID
     *
     * * Timeline:
     * * 1. Student::create(['name' => 'John']) called
     * * 2. 'creating' event fires → UUID generated
     * * 3. Record saved to database with UUID
     */
    protected static function boot(): void
    {
        // * Always call parent boot first (required)
        parent::boot();

        // * Register callback for 'creating' event (before INSERT)
        static::creating(function ($student): void {
            // * Only generate UUID if not already set
            if (empty($student->uuid)) {
                // * Str::uuid() generates a universally unique identifier
                // * Example: "550e8400-e29b-41d4-a716-446655440000"
                $student->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * ! RELATIONSHIP: Student has many Lessons (One-to-Many)
     *
     * * One student can have hundreds of lesson records
     * * Database: lessons.student_id → students.id
     *
     * ? HasMany is the inverse of BelongsTo
     * ? Student hasMany Lessons ←→ Lesson belongsTo Student
     *
     * * Usage: $student->lessons              → Collection of Lesson models
     * * Usage: $student->lessons()->count()   → Number of lessons
     * * Usage: $student->lessons()->where('status', 'completed')->get()
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * ! RELATIONSHIP: Student belongs to many Teachers (Many-to-Many)
     *
     * * A student can have multiple teachers
     * * A teacher can have multiple students
     * * This requires a PIVOT TABLE: student_teacher
     *
     * ? BelongsToMany creates a many-to-many relationship
     * ? Laravel automatically handles the pivot table queries
     *
     * * Usage: $student->teachers            → Collection of Teacher models
     * * Usage: $student->teachers()->attach($teacherId)   → Assign teacher
     * * Usage: $student->teachers()->detach($teacherId)   → Remove teacher
     * * Usage: $student->teachers()->sync([1, 2, 3])      → Set exact teachers
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }

    /**
     * ! ROUTE MODEL BINDING - Use UUID instead of ID in URLs
     *
     * ? What is Route Model Binding?
     * ? Laravel can automatically find a model from a URL parameter
     * ? Route: /student/{student} → Controller receives Student model
     *
     * * By default, Laravel uses 'id' column
     * * We override to use 'uuid' for security/privacy
     *
     * * URL: /student/550e8400-e29b-41d4-a716-446655440000
     * * Instead of: /student/1 (exposes sequential IDs)
     *
     * ! Security: UUIDs are unguessable, IDs are sequential
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * ! QUERY SCOPE: Load student with all related data (Eager Loading)
     *
     * * Prevents N+1 query problems by loading relationships upfront
     *
     * ? N+1 Problem Example (BAD):
     * ? foreach($students as $student) {
     * ?     echo $student->teachers->count();  // ← Separate query per student!
     * ? }
     * ? Result: 1 query for students + N queries for teachers = N+1 queries
     *
     * * Solution - Eager Load (GOOD):
     * * Student::withFullDetails()->get();
     * * Result: 1 query for students + 1 query for teachers = 2 queries
     *
     * * withCount('teachers', 'lessons'): Adds teachers_count, lessons_count
     * * with('teachers'): Loads the actual Teacher models
     */
    public function scopeWithFullDetails($query)
    {
        return $query->withCount('teachers', 'lessons')->with('teachers');
    }

    /**
     * ! QUERY SCOPE: Filter to only active students
     *
     * * Usage: Student::active()->get()
     * *   → WHERE status = 'active'
     */
    public function scopeActive($query)
    {
        return $query->where('status', StudentStatus::ACTIVE);
    }

    /**
     * ! QUERY SCOPE: Filter to enrolled students (active OR holiday)
     *
     * * "Enrolled" means still a student, just maybe on vacation
     * * Excludes 'inactive' students who have stopped lessons
     *
     * * Usage: Student::enrolled()->get()
     * *   → WHERE status IN ('active', 'holiday')
     */
    public function scopeEnrolled($query)
    {
        return $query->whereIn('status', [StudentStatus::ACTIVE, StudentStatus::HOLIDAY]);
    }
}
