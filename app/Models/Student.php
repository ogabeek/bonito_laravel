<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Student - Represents a tutoring student
 *
 * Uses UUID for public-facing URLs instead of sequential IDs.
 * Many-to-many with Teachers via student_teacher pivot table.
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'parent_name',
        'email',
        'goal',
        'description',
        'status',       // active, inactive, holiday
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
        ];
    }

    // Auto-generate UUID on creation
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($student): void {
            if (empty($student->uuid)) {
                $student->uuid = (string) Str::uuid();
            }
        });
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    // Many-to-many via student_teacher pivot
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }

    // Route model binding uses UUID, not ID
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Eager load with counts to prevent N+1
    public function scopeWithFullDetails($query)
    {
        return $query->withCount('teachers', 'lessons')->with('teachers');
    }

    public function scopeActive($query)
    {
        return $query->where('status', StudentStatus::ACTIVE);
    }

    // Active OR holiday (still enrolled)
    public function scopeEnrolled($query)
    {
        return $query->whereIn('status', [StudentStatus::ACTIVE, StudentStatus::HOLIDAY]);
    }
}
