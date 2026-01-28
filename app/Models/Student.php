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

    protected $fillable = [ // fields that can be mass-assigned
        'uuid',
        'name',
        'parent_name',
        'email',
        'goal',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => StudentStatus::class,
    ];

    protected $attributes = [
        'status' => 'active', // Default status for new students (uses StudentStatus::ACTIVE)
    ];

    // Automatically generate UUI when creating a student
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
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
