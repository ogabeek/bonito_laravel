<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Teacher - Represents a tutor who conducts lessons
 *
 * Uses SoftDeletes to preserve lesson history when teachers leave.
 * Password-only auth (no email) - access via /teacher/{id}.
 */
class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'password',  // Always hash before saving
        'contact',
        'zoom_link',
        'zoom_id',
        'zoom_passcode',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    // Many-to-many via student_teacher pivot
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    // Eager load with counts to prevent N+1
    public function scopeWithFullDetails($query)
    {
        return $query->withCount('students', 'lessons')->with('students');
    }
}
