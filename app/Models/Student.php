<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
        'status',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
        ];
    }

    /**
     * Boot method to auto-generate UUID on creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($student): void {
            if (empty($student->uuid)) {
                $student->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationship: a student has many lessons
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    // Relationship: a student belongs to many teachers (many-to-many)
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }

    // Use UUID for route model binding instead of ID
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Scope: Load student with full details (teachers and lessons count)
    public function scopeWithFullDetails($query)
    {
        return $query->withCount('teachers', 'lessons')->with('teachers');
    }

    // Scope: Filter active students only
    public function scopeActive($query)
    {
        return $query->where('status', StudentStatus::ACTIVE);
    }

    // Scope: Filter enrolled students (active or on holiday)
    public function scopeEnrolled($query)
    {
        return $query->whereIn('status', [StudentStatus::ACTIVE, StudentStatus::HOLIDAY]);
    }
}
