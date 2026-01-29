<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ! MODEL: Lesson - The Core Business Entity
 *
 * * This is the central model of the tutoring application.
 * * A Lesson represents a single tutoring session between a Teacher and Student.
 *
 * ? What is a Model in Laravel?
 * ? - A Model represents a database table (lessons table)
 * ? - Each instance of Lesson = one row in the database
 * ? - Models handle data retrieval, insertion, updates, and relationships
 *
 * * Database Table Structure (lessons):
 * * ┌──────────────┬─────────────────┬──────────────────────────────────┐
 * * │ Column       │ Type            │ Description                      │
 * * ├──────────────┼─────────────────┼──────────────────────────────────┤
 * * │ id           │ integer (PK)    │ Auto-increment primary key       │
 * * │ teacher_id   │ integer (FK)    │ Links to teachers table          │
 * * │ student_id   │ integer (FK)    │ Links to students table          │
 * * │ class_date   │ date            │ When the lesson occurred         │
 * * │ status       │ string (enum)   │ completed/cancelled/absent       │
 * * │ topic        │ string          │ What was taught                  │
 * * │ homework     │ string          │ Assigned homework                │
 * * │ comments     │ text            │ Additional notes                 │
 * * │ created_at   │ timestamp       │ Auto-managed by Laravel          │
 * * │ updated_at   │ timestamp       │ Auto-managed by Laravel          │
 * * │ deleted_at   │ timestamp       │ For soft deletes (null = active) │
 * * └──────────────┴─────────────────┴──────────────────────────────────┘
 */
class Lesson extends Model
{
    /**
     * ? TRAITS - Reusable functionality added to the class
     *
     * * HasFactory: Enables Lesson::factory() for creating test data
     * *   Example: Lesson::factory()->create() // Creates a fake lesson
     *
     * * SoftDeletes: Instead of permanently deleting, sets deleted_at timestamp
     * *   Example: $lesson->delete() // Sets deleted_at, doesn't remove row
     * *   Example: Lesson::withTrashed()->get() // Includes "deleted" lessons
     */
    use HasFactory, SoftDeletes;

    /**
     * ! MASS ASSIGNMENT PROTECTION
     *
     * * $fillable = whitelist of fields that can be mass-assigned
     *
     * ? What is mass assignment?
     * ? When you do: Lesson::create(['teacher_id' => 1, 'topic' => 'Math'])
     * ? Laravel will ONLY set fields listed in $fillable
     * ? This prevents hackers from setting fields like 'id' or 'created_at'
     *
     * * Security: Fields NOT in this array cannot be set via create() or update()
     */
    protected $fillable = [
        'teacher_id',   // * Which teacher conducted the lesson
        'student_id',   // * Which student attended
        'class_date',   // * Date of the lesson
        'status',       // * completed, student_cancelled, teacher_cancelled, student_absent
        'topic',        // * What was taught (required for completed lessons)
        'homework',     // * Optional homework assignment
        'comments',     // * Optional notes (required for teacher_cancelled)
    ];

    /**
     * ! ATTRIBUTE CASTING
     *
     * * Casts automatically convert database values to PHP types
     *
     * ? Why cast?
     * ? Database stores: '2024-01-15' (string), 'completed' (string)
     * ? After casting:  Carbon date object, LessonStatus enum object
     *
     * * This means:
     * * $lesson->class_date->format('F j') → "January 15" (Carbon methods)
     * * $lesson->status->label() → "Completed" (Enum methods)
     */
    protected function casts(): array
    {
        return [
            'class_date' => 'date',              // * String → Carbon date object
            'status' => LessonStatus::class,     // * String → LessonStatus enum
        ];
    }

    /**
     * ! RELATIONSHIP: Lesson belongs to a Teacher
     *
     * * Database: lessons.teacher_id → teachers.id (foreign key)
     *
     * ? BelongsTo = "This model has a foreign key pointing to another model"
     * ? The opposite would be HasMany (Teacher hasMany Lessons)
     *
     * * Usage: $lesson->teacher         → Returns Teacher model
     * * Usage: $lesson->teacher->name   → "John Smith"
     *
     * ! withTrashed() includes soft-deleted teachers
     * ! Why? If a teacher is archived, we still want to see their name on old lessons
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class)->withTrashed();
    }

    /**
     * ! RELATIONSHIP: Lesson belongs to a Student
     *
     * * Database: lessons.student_id → students.id (foreign key)
     *
     * * Usage: $lesson->student         → Returns Student model
     * * Usage: $lesson->student->name   → "Alice Johnson"
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * ! QUERY SCOPE: Filter lessons by month
     *
     * * Scopes are reusable query constraints
     *
     * ? How to use scopes:
     * ? Method name: scopeForMonth → Use as: ->forMonth($date)
     * ? Laravel automatically removes "scope" prefix and lowercases first letter
     *
     * * Example usage:
     * * Lesson::forMonth(Carbon::parse('2024-01'))
     * *   → WHERE YEAR(class_date) = 2024 AND MONTH(class_date) = 1
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  // ? Auto-injected by Laravel
     * @param  \Carbon\Carbon  $date  // * Any date in the target month
     */
    public function scopeForMonth($query, $date)
    {
        return $query->whereYear('class_date', $date->year)
            ->whereMonth('class_date', $date->month);
    }

    /**
     * ! QUERY SCOPE: Filter to only past lessons
     *
     * * Example: Lesson::past()->get()
     * *   → WHERE class_date < TODAY
     *
     * * Used on student dashboard to show completed lessons history
     */
    public function scopePast($query)
    {
        return $query->where('class_date', '<', now()->startOfDay());
    }

    /**
     * ! QUERY SCOPE: Filter by specific status
     *
     * * Example: Lesson::withStatus(LessonStatus::COMPLETED)->get()
     * *   → WHERE status = 'completed'
     *
     * @param  LessonStatus  $status  // * The enum value to filter by
     */
    public function scopeWithStatus($query, LessonStatus $status)
    {
        return $query->where('status', $status);
    }
}
